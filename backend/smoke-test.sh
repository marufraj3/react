#!/usr/bin/env bash
# =============================================================================
# Shop Genie — end-to-end storefront API smoke test
#
# Usage:
#   bash smoke-test.sh                          # against http://localhost:8000
#   bash smoke-test.sh https://api.example.com  # against a deployed server
#
# Exercises every storefront endpoint in order (bootstrap → order → auth →
# refund). Requires `curl` and `jq` (jq optional — used only for pretty output).
# =============================================================================
set -uo pipefail

BASE="${1:-http://localhost:8000}"
BASE="${BASE%/}"
PASS=0
FAIL=0

ok()   { PASS=$((PASS+1)); printf '  ✅ %s\n' "$1"; }
fail() { FAIL=$((FAIL+1)); printf '  ❌ %s\n' "$1"; }

# request METHOD PATH [BODY] [TOKEN] -> sets RESP (body) and CODE (status)
request() {
  local method="$1" path="$2" body="${3:-}" token="${4:-}"
  local args=(-sS -X "$method" -H "Accept: application/json" -H "Content-Type: application/json")
  [ -n "$token" ] && args+=(-H "Authorization: Bearer $token")
  [ -n "$body" ] && args+=(-d "$body")
  local out
  out=$(curl "${args[@]}" -w '\n%{http_code}' "$BASE/api/v1/storefront$path" 2>/dev/null)
  CODE=$(printf '%s' "$out" | tail -n1)
  RESP=$(printf '%s' "$out" | sed '$d')
}

echo "========================================================"
echo " Smoke testing: $BASE/api/v1/storefront"
echo "========================================================"

# ---- 1. Bootstrap -----------------------------------------------------
echo "› bootstrap"
request GET /bootstrap
[ "$CODE" = 200 ] && ok "bootstrap 200" || fail "bootstrap ($CODE)"
PRODUCT_ID=$(printf '%s' "$RESP" | sed -n 's/.*"id":\([0-9]*\).*/\1/p' | head -1)
# extract first product id via jq if available, else via grep fallback
if command -v jq >/dev/null 2>&1; then
  PRODUCT_ID=$(printf '%s' "$RESP" | jq -r '.data.products[0].id // empty' 2>/dev/null)
  PRODUCT_SLUG=$(printf '%s' "$RESP" | jq -r '.data.products[0].slug // empty' 2>/dev/null)
fi
[ -n "$PRODUCT_ID" ] && ok "found product id=$PRODUCT_ID" || fail "no product in bootstrap"

# ---- 2. Products list --------------------------------------------------
echo "› products"
request GET "/products"
[ "$CODE" = 200 ] && ok "products 200" || fail "products ($CODE)"

# ---- 3. Search ----------------------------------------------------------
echo "› search"
request GET "/search?q=smartwatch"
[ "$CODE" = 200 ] && ok "search 200" || fail "search ($CODE)"

# ---- 4. Product detail --------------------------------------------------
echo "› product detail"
request GET "/products/${PRODUCT_ID}"
[ "$CODE" = 200 ] && ok "product detail 200" || fail "product detail ($CODE)"

# ---- 5. Blogs ------------------------------------------------------------
echo "› blogs"
request GET "/blogs"
[ "$CODE" = 200 ] && ok "blogs 200" || fail "blogs ($CODE)"

# ---- 6. Coupon apply ------------------------------------------------------
echo "› coupon apply"
request POST /coupons/apply '{"code":"SAVE100","subtotal":2000,"phone":"01700000000"}'
[ "$CODE" = 200 ] && ok "coupon apply 200" || fail "coupon apply ($CODE)"

# ---- 7. Register ----------------------------------------------------------
PHONE="018$(date +%s | tail -c 9)"
echo "› register ($PHONE)"
request POST /auth/register "{\"name\":\"Smoke Tester\",\"phone\":\"$PHONE\",\"password\":\"secret123\"}"
[ "$CODE" = 201 ] && ok "register 201" || fail "register ($CODE)"
TOKEN=$(printf '%s' "$RESP" | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')
[ -n "$TOKEN" ] && ok "token issued" || fail "no token in register response"

# ---- 8. Me ----------------------------------------------------------------
echo "› auth/me"
request GET /auth/me "" "$TOKEN"
[ "$CODE" = 200 ] && ok "me 200" || fail "me ($CODE)"

# ---- 9. Create COD order ---------------------------------------------------
echo "› create order (COD)"
ORDER_BODY="{\"name\":\"Smoke Tester\",\"phone\":\"$PHONE\",\"address\":\"Dhanmondi, Dhaka\",\"area\":1,\"payment_method\":\"cod\",\"items\":[{\"product_id\":${PRODUCT_ID},\"qty\":1,\"price\":2490}]}"
request POST /orders "$ORDER_BODY"
[ "$CODE" = 201 ] && ok "create order 201" || fail "create order ($CODE)"
ORDER_ID=$(printf '%s' "$RESP" | sed -n 's/.*"order_id":"\([^"]*\)".*/\1/p')
[ -n "$ORDER_ID" ] && ok "order id=$ORDER_ID" || fail "no order_id in response"

# ---- 10. Track order --------------------------------------------------------
echo "› track order"
request GET "/orders/track/${ORDER_ID}?phone=$PHONE"
[ "$CODE" = 200 ] && ok "track order 200" || fail "track order ($CODE)"

# ---- 11. My orders ----------------------------------------------------------
echo "› auth/orders"
request GET /auth/orders "" "$TOKEN"
[ "$CODE" = 200 ] && ok "auth/orders 200" || fail "auth/orders ($CODE)"

# ---- 12. Profile update -------------------------------------------------------
echo "› profile update"
request POST /auth/profile "{\"name\":\"Smoke Tester Updated\",\"phone\":\"$PHONE\",\"address\":\"Uttara, Dhaka\"}" "$TOKEN"
[ "$CODE" = 200 ] && ok "profile 200" || fail "profile ($CODE)"

# ---- 13. Change password -------------------------------------------------------
echo "› change password"
request POST /auth/password '{"old_password":"secret123","new_password":"secret456","confirm_password":"secret456"}' "$TOKEN"
[ "$CODE" = 200 ] && ok "password 200" || fail "password ($CODE)"

# ---- 14. Refund request ---------------------------------------------------------
echo "› refund request"
request POST /refunds "{\"order_id\":\"$ORDER_ID\",\"reason\":\"Test refund\",\"refund_method\":\"bkash\",\"refund_account\":\"$PHONE\"}" "$TOKEN"
[ "$CODE" = 201 ] && ok "refund 201" || fail "refund ($CODE)"

# ---- 15. My refunds -------------------------------------------------------------
echo "› auth/refunds"
request GET /auth/refunds "" "$TOKEN"
[ "$CODE" = 200 ] && ok "auth/refunds 200" || fail "auth/refunds ($CODE)"

# ---- 16. Complaint + contact + review ---------------------------------------------
echo "› complaint"
request POST /complaints '{"name":"Smoke Tester","phone":"'"$PHONE"'","message":"Smoke complaint"}'
[ "$CODE" = 201 ] && ok "complaint 201" || fail "complaint ($CODE)"

echo "› contact"
request POST /contact '{"name":"Smoke Tester","phone":"'"$PHONE"'","message":"Smoke contact"}'
[ "$CODE" = 201 ] && ok "contact 201" || fail "contact ($CODE)"

echo "› review"
request POST /reviews "{\"product_id\":${PRODUCT_ID},\"rating\":5,\"comment\":\"Great\"}"
[ "$CODE" = 201 ] && ok "review 201" || fail "review ($CODE)"

echo ""
echo "========================================================"
echo " PASSED: $PASS   FAILED: $FAIL"
echo "========================================================"
[ "$FAIL" -eq 0 ] && exit 0 || exit 1
