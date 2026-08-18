{{--
    ======================================================================
    GLOBAL QUICK-ORDER POPUP  (Order Now / Cart বাটনে ক্লিকে পপআপ)
    ----------------------------------------------------------------------
    Flow (popup-এর ভেতরেই অর্ডার কমপ্লিট):
      Product → Order Now → Size Select → Quantity → Customer Information
      → Confirm Order → ✅ Success (invoice নম্বর সহ)

    • সব frontend পেজে master.blade.php এর মাধ্যমে include হয়।
    • প্রোডাক্ট ডাটা `/quick-order/{id}` endpoint থেকে fetch (ক্যাশড),
      অর্ডার প্লেসমেন্ট `POST /quick-order/place` → QuickOrderService
      (existing order system-এর সাথে fully integrated)।
    • Admin → General Settings থেকে সব নিয়ন্ত্রণ (on/off, টাইটেল, বাটন টেক্সট)।
    ======================================================================
--}}
@php
    $qoPrimary    = $generalsetting->primary_color  ?? '#303d6e';
    $qoSecondary  = $generalsetting->secodery_color ?? '#ff0000';
    $qoEnabled    = (int)(($generalsetting->quick_order_popup_enabled ?? 1) == 1);
    $qoTitle      = $generalsetting->quick_order_popup_title ?? '🛒 দ্রুত অর্ডার করুন';
    $qoConfirm    = $generalsetting->quick_order_confirm_text ?? 'অর্ডার কনফার্ম করুন →';
    $qoCartText   = $generalsetting->quick_order_cart_text ?? 'কার্টে রাখুন';
    $qoToast      = $generalsetting->quick_order_cart_toast ?? 'কার্টে যোগ হয়েছে ✔';
    $qoHotline    = optional($contact)->hotline ?? optional($contact)->whatsapp ?? '';
    $qoCustomer   = Auth::guard('customer')->user();
@endphp

<style>
:root{
    --qo-primary   : {{ $qoPrimary }};
    --qo-secondary : {{ $qoSecondary }};
    --qo-dark      : #131a22;
    --qo-text      : #1f2937;
    --qo-muted     : #6b7280;
    --qo-line      : #e9edf2;
    --qo-green     : #12a150;
    --qo-red       : #e11d48;
}
.qo-modal{position:fixed;inset:0;z-index:99998;display:none;font-family:'Hind Siliguri','Segoe UI',system-ui,-apple-system,sans-serif;}
.qo-modal.on{display:block;}
.qo-bg{position:absolute;inset:0;background:rgba(15,23,42,.62);backdrop-filter:blur(2px);}
.qo-box{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(860px,94vw);max-height:92vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.3);animation:qo-up .28s cubic-bezier(.2,.8,.2,1);}
@keyframes qo-up{from{opacity:0;transform:translate(-50%,-46%);}to{opacity:1;transform:translate(-50%,-50%);}}
.qo-head{position:sticky;top:0;background:#fff;z-index:5;display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--qo-line);}
.qo-head h5{margin:0;font-size:15.5px;font-weight:800;color:var(--qo-primary);display:flex;align-items:center;gap:8px;}
.qo-steps{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;color:var(--qo-muted);}
.qo-steps .st{display:inline-flex;align-items:center;gap:5px;}
.qo-steps .dot{width:18px;height:18px;border-radius:50%;background:#eef0f5;color:var(--qo-muted);display:inline-flex;align-items:center;justify-content:center;font-size:10px;}
.qo-steps .st.on{color:var(--qo-primary);}
.qo-steps .st.on .dot{background:var(--qo-primary);color:#fff;}
.qo-steps .st.done .dot{background:var(--qo-green);color:#fff;}
.qo-steps .sep{color:#c9cfd9;font-size:12px;}
.qo-x{border:0;background:#f1f3f7;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:15px;line-height:1;flex:0 0 32px;}
.qo-x:hover{background:var(--qo-secondary);color:#fff;}

/* ---------- steps ---------- */
.qo-step{display:none;padding:18px;}
.qo-step.on{display:block;}
.qo-body{display:grid;grid-template-columns:minmax(0,240px) minmax(0,1fr);gap:18px;}
.qo-img{border-radius:12px;overflow:hidden;border:1px solid var(--qo-line);background:#f7f9fc;position:relative;}
.qo-img img{width:100%;aspect-ratio:1/1;object-fit:cover;display:block;}
.qo-img .qo-img-badge{position:absolute;top:8px;left:8px;background:var(--qo-secondary);color:#fff;font-size:11px;font-weight:800;padding:3px 8px;border-radius:7px;z-index:2;}
.qo-name{font-size:16px;font-weight:700;line-height:1.4;margin:0 0 8px;color:var(--qo-text);}
.qo-price{display:flex;align-items:baseline;gap:9px;flex-wrap:wrap;margin-bottom:6px;}
.qo-price b{font-size:24px;font-weight:800;color:var(--qo-secondary);}
.qo-price del{color:var(--qo-muted);font-size:15px;}
.qo-save{background:#e8f7ee;color:var(--qo-green);font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:20px;}
.qo-stock{font-size:12.5px;color:var(--qo-secondary);font-weight:600;margin-bottom:12px;}
.qo-lbl{font-size:13px;font-weight:800;margin:14px 0 7px;display:flex;align-items:center;gap:6px;color:var(--qo-text);}
.qo-lbl:first-child{margin-top:0;}
.qo-lbl em{font-style:normal;color:var(--qo-secondary);}
.qo-lbl .qo-req{font-size:11px;font-weight:600;color:var(--qo-muted);}
.qo-chips{display:flex;flex-wrap:wrap;gap:8px;}
.qo-chip{border:1.5px solid #dfe3e8;background:#fff;min-width:52px;padding:7px 14px 6px;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.18s;font-family:inherit;position:relative;text-align:center;}
.qo-chip-stock{display:block;font-size:10px;color:var(--qo-green);margin-top:2px;font-weight:600;white-space:nowrap;}
.qo-chip.qo-off .qo-chip-stock{color:var(--qo-red);}
.qo-chip:hover{border-color:var(--qo-primary);}
.qo-chip.on{border-color:var(--qo-primary);background:var(--qo-primary);color:#fff;box-shadow:0 4px 12px rgba(48,61,110,.28);}
.qo-chip .qo-dot{display:inline-block;width:13px;height:13px;border-radius:50%;border:1px solid rgba(0,0,0,.15);margin-right:6px;vertical-align:-2px;}
.qo-chips.qo-err .qo-chip{border-color:var(--qo-red);}
.qo-chip.qo-off{opacity:.38;cursor:not-allowed;text-decoration:line-through;background:#f5f6f8;}
.qo-chip.qo-off:hover{border-color:#dfe3e8;}
.qo-shake{animation:qo-sh .4s;}
@keyframes qo-sh{0%,100%{transform:translateX(0);}25%{transform:translateX(-6px);}75%{transform:translateX(6px);}}
.qo-qty{display:flex;align-items:center;gap:0;border:1.5px solid #dfe3e8;border-radius:9px;width:fit-content;overflow:hidden;}
.qo-qty button{width:40px;height:40px;border:0;background:#f7f8fa;font-size:18px;cursor:pointer;font-family:inherit;color:var(--qo-primary);font-weight:700;}
.qo-qty button:hover{background:var(--qo-primary);color:#fff;}
.qo-qty input{width:56px;height:40px;border:0;text-align:center;font-size:15px;font-weight:700;outline:none;font-family:inherit;}
.qo-total{display:flex;justify-content:space-between;align-items:center;background:#f7f9fc;border:1px dashed #cfd8e3;border-radius:10px;padding:11px 14px;margin-top:14px;}
.qo-total span{font-size:13.5px;font-weight:700;color:var(--qo-muted);}
.qo-total b{font-size:21px;font-weight:800;color:var(--qo-primary);}
.qo-btns{display:grid;grid-template-columns:1fr auto;gap:9px;margin-top:16px;}
.qo-btns--single{grid-template-columns:1fr;}
.qo-next{border:0;cursor:pointer;background:linear-gradient(90deg,var(--qo-secondary),#ff6a3d);color:#fff;font-size:15.5px;font-weight:800;padding:14px 22px;border-radius:11px;font-family:inherit;box-shadow:0 8px 20px rgba(255,0,0,.24);transition:.22s;display:flex;align-items:center;justify-content:center;gap:8px;}
.qo-next:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(255,0,0,.34);}
.qo-next:active{transform:none;}
.qo-next:disabled{opacity:.6;cursor:not-allowed;transform:none;box-shadow:none;}
.qo-addcart{border:1.5px solid var(--qo-primary);background:#fff;color:var(--qo-primary);font-size:14px;font-weight:700;padding:0 18px;border-radius:11px;cursor:pointer;font-family:inherit;transition:.2s;}
.qo-addcart:hover{background:var(--qo-primary);color:#fff;}
.qo-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px;padding-top:13px;border-top:1px solid var(--qo-line);}
.qo-trust div{text-align:center;font-size:11px;color:var(--qo-muted);line-height:1.4;font-weight:600;}
.qo-trust i{display:block;font-style:normal;font-size:17px;margin-bottom:3px;}
.qo-help{text-align:center;font-size:12.5px;color:var(--qo-muted);margin-top:11px;}
.qo-help a{color:var(--qo-primary);font-weight:700;}
.qo-view{display:block;text-align:center;font-size:12.5px;margin-top:8px;color:var(--qo-muted);text-decoration:underline;}
.qo-loading{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);color:var(--qo-primary);font-weight:700;font-size:14px;}

/* ---------- customer info step ---------- */
.qo-cust{display:grid;grid-template-columns:minmax(0,240px) minmax(0,1fr);gap:18px;}
.qo-summary{background:#f7f9fc;border:1px solid var(--qo-line);border-radius:12px;padding:14px;align-self:start;}
.qo-summary__row{display:flex;gap:10px;align-items:center;padding-bottom:11px;border-bottom:1px dashed #dbe2ec;margin-bottom:11px;}
.qo-summary__row img{width:58px;height:58px;object-fit:cover;border-radius:9px;border:1px solid var(--qo-line);flex:0 0 58px;}
.qo-summary__row b{font-size:12.5px;line-height:1.4;display:block;margin-bottom:4px;color:var(--qo-text);}
.qo-summary__row small{font-size:11.5px;color:var(--qo-muted);font-weight:600;}
.qo-summary li{display:flex;justify-content:space-between;font-size:12.5px;color:var(--qo-muted);font-weight:600;padding:4px 0;}
.qo-summary li b{color:var(--qo-text);font-weight:700;}
.qo-summary li.qo-grand{border-top:1px dashed #dbe2ec;margin-top:6px;padding-top:10px;font-size:13.5px;color:var(--qo-text);}
.qo-summary li.qo-grand b{font-size:19px;color:var(--qo-primary);}
.qo-field{margin-bottom:12px;}
.qo-field label{display:block;font-size:12.5px;font-weight:800;color:var(--qo-text);margin-bottom:5px;}
.qo-field label em{color:var(--qo-red);font-style:normal;}
.qo-field input,.qo-field textarea{width:100%;border:1.5px solid #dfe3e8;border-radius:9px;padding:11px 13px;font-size:14px;font-family:inherit;outline:none;transition:.18s;background:#fff;color:var(--qo-text);}
.qo-field input:focus,.qo-field textarea:focus{border-color:var(--qo-primary);box-shadow:0 0 0 3px rgba(48,61,110,.1);}
.qo-field textarea{resize:vertical;min-height:70px;}
.qo-field.err input,.qo-field.err textarea{border-color:var(--qo-red);}
.qo-cod{display:flex;align-items:center;gap:9px;background:#e8f7ee;border:1px solid #cdeeda;border-radius:10px;padding:10px 13px;font-size:12.5px;font-weight:700;color:#0a7a3d;margin-top:4px;}
.qo-cod i{font-style:normal;font-size:17px;}
.qo-back{border:1.5px solid #dfe3e8;background:#fff;color:var(--qo-muted);font-size:14px;font-weight:700;padding:0 18px;border-radius:11px;cursor:pointer;font-family:inherit;transition:.2s;}
.qo-back:hover{border-color:var(--qo-primary);color:var(--qo-primary);}

/* ---------- success step ---------- */
.qo-success{text-align:center;padding:36px 20px 30px;}
.qo-success__ico{width:78px;height:78px;border-radius:50%;background:#e8f7ee;color:var(--qo-green);display:inline-flex;align-items:center;justify-content:center;font-size:36px;margin-bottom:16px;animation:qo-pop .4s cubic-bezier(.2,.8,.2,1);}
@keyframes qo-pop{from{transform:scale(.4);opacity:0;}to{transform:scale(1);opacity:1;}}
.qo-success h3{font-size:21px;font-weight:800;color:var(--qo-text);margin:0 0 6px;}
.qo-success p{color:var(--qo-muted);font-size:14px;margin:0 0 18px;}
.qo-success__inv{display:inline-flex;align-items:center;gap:10px;background:#f7f9fc;border:1.5px dashed #cfd8e3;border-radius:12px;padding:12px 22px;font-size:14px;font-weight:700;color:var(--qo-muted);margin-bottom:22px;}
.qo-success__inv b{color:var(--qo-primary);font-size:17px;}
.qo-success__btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
@media(max-width:640px){
    .qo-box{top:auto;bottom:0;left:0;transform:none;width:100%;max-height:94vh;border-radius:18px 18px 0 0;animation:qo-sheet .3s cubic-bezier(.2,.8,.2,1);}
    @keyframes qo-sheet{from{transform:translateY(100%);}to{transform:translateY(0);}}
    .qo-body,.qo-cust{grid-template-columns:1fr;gap:14px;}
    .qo-img{max-width:170px;margin:0 auto;}
    .qo-step{padding:14px;}
    .qo-steps{display:none;}
    .qo-btns{grid-template-columns:1fr 1fr;position:sticky;bottom:-14px;background:#fff;padding:10px 0 0;margin-top:14px;}
    .qo-addcart{padding:0 8px;font-size:13px;}
    .qo-btns--single{grid-template-columns:1fr;}
}
</style>

<div class="qo-modal" id="qoModal" aria-hidden="true">
    <div class="qo-bg" onclick="qoClose()"></div>
    <div class="qo-box">
        <div class="qo-head">
            <h5 id="qoTitle">{{ $qoTitle }}</h5>
            <div class="qo-steps">
                <span class="st on" id="qoSt1"><span class="dot">1</span> অপশন</span>
                <span class="sep">→</span>
                <span class="st" id="qoSt2"><span class="dot">2</span> তথ্য</span>
                <span class="sep">→</span>
                <span class="st" id="qoSt3"><span class="dot">3</span> কনফার্ম</span>
            </div>
            <button class="qo-x" type="button" onclick="qoClose()" aria-label="Close">✕</button>
        </div>

        {{-- ============ STEP 1 — সাইজ / কালার / পরিমাণ ============ --}}
        <div class="qo-step on" id="qoStep1">
            <div class="qo-body">
                <div>
                    <div class="qo-img">
                        <span class="qo-img-badge" id="qoImgBadge" style="display:none"></span>
                        <img id="qoImg" src="" alt="Product">
                    </div>
                    <a class="qo-view" id="qoLink" href="#">বিস্তারিত দেখুন</a>
                </div>

                <div>
                    <h4 class="qo-name" id="qoName"></h4>
                    <div class="qo-price">
                        <b id="qoPrice"></b>
                        <del id="qoOld" style="display:none"></del>
                        <span class="qo-save" id="qoSave" style="display:none"></span>
                    </div>
                    <div class="qo-stock" id="qoStock"></div>

                    <div id="qoSizeWrap" style="display:none">
                        <p class="qo-lbl">সাইজ সিলেক্ট করুন <em>*</em> <span class="qo-req" id="qoSizePick"></span></p>
                        <div class="qo-chips" id="qoSizes"></div>
                    </div>

                    <div id="qoColorWrap" style="display:none">
                        <p class="qo-lbl">কালার সিলেক্ট করুন <em>*</em> <span class="qo-req" id="qoColorPick"></span></p>
                        <div class="qo-chips" id="qoColors"></div>
                    </div>

                    <p class="qo-lbl">পরিমাণ</p>
                    <div class="qo-qty">
                        <button type="button" onclick="qoQty(-1)">−</button>
                        <input type="text" id="qoQtyBox" value="1" readonly>
                        <button type="button" onclick="qoQty(1)">+</button>
                    </div>

                    <div class="qo-total">
                        <span>সর্বমোট</span>
                        <b id="qoTotal">৳ 0</b>
                    </div>

                    <div class="qo-btns" id="qoBtns1">
                        <button type="button" class="qo-addcart" id="qoAddCartBtn"><i class="fa-solid fa-cart-plus"></i> {{ $qoCartText }}</button>
                        <button type="button" class="qo-next" id="qoNextBtn">পরবর্তী — আপনার তথ্য দিন <i class="fa-solid fa-arrow-right"></i></button>
                    </div>

                    <div class="qo-trust">
                        <div><i>🚚</i> ক্যাশ অন ডেলিভারি</div>
                        <div><i>🔄</i> সহজ রিটার্ন</div>
                        <div><i>✅</i> ১০০% অরিজিনাল</div>
                    </div>

                    @if(!empty($qoHotline))
                    <p class="qo-help">যেকোনো সাহায্যে কল করুন: <a href="tel:{{ $qoHotline }}">{{ $qoHotline }}</a></p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============ STEP 2 — কাস্টমার তথ্য ============ --}}
        <div class="qo-step" id="qoStep2">
            <form id="qoForm" onsubmit="return qoSubmitOrder(event)">
                @csrf
                <input type="hidden" name="id" id="qoId">
                <input type="hidden" name="product_size" id="qoSize">
                <input type="hidden" name="product_color" id="qoColor">
                <input type="hidden" name="qty" id="qoQty" value="1">

                <div class="qo-cust">
                    <div class="qo-summary">
                        <div class="qo-summary__row">
                            <img id="qoSImg" src="" alt="">
                            <div>
                                <b id="qoSName"></b>
                                <small id="qoSOpts"></small>
                            </div>
                        </div>
                        <ul style="list-style:none;margin:0;padding:0">
                            <li><span>সাবটোটাল</span><b id="qoSSubtotal"></b></li>
                            <li><span>ডেলিভারি চার্জ</span><b id="qoSShipping">৳ 0</b></li>
                            <li class="qo-grand"><span>সর্বমোট</span><b id="qoSGrand"></b></li>
                        </ul>
                        <div class="qo-cod"><i>💵</i> ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে টাকা দিন</div>
                    </div>

                    <div>
                        <div class="qo-field" id="qoFName">
                            <label>আপনার নাম <em>*</em></label>
                            <input type="text" name="name" id="qoNameInp" placeholder="সম্পূর্ণ নাম লিখুন" autocomplete="name">
                        </div>
                        <div class="qo-field" id="qoFPhone">
                            <label>মোবাইল নম্বর <em>*</em></label>
                            <input type="tel" name="phone" id="qoPhoneInp" placeholder="01XXXXXXXXX" autocomplete="tel">
                        </div>
                        <div class="qo-field" id="qoFAddr">
                            <label>সম্পূর্ণ ঠিকানা <em>*</em></label>
                            <textarea name="address" id="qoAddrInp" placeholder="বাসা/হোল্ডিং, রোড, থানা/এরিয়া, জেলা" autocomplete="street-address"></textarea>
                        </div>

                        <div class="qo-btns">
                            <button type="button" class="qo-back" onclick="qoGoStep(1)"><i class="fa-solid fa-arrow-left"></i> পিছনে</button>
                            <button type="submit" class="qo-next" id="qoConfirm">{{ $qoConfirm }}</button>
                        </div>

                        @if(!empty($qoHotline))
                        <p class="qo-help">যেকোনো সাহায্যে কল করুন: <a href="tel:{{ $qoHotline }}">{{ $qoHotline }}</a></p>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ============ STEP 3 — সফল ============ --}}
        <div class="qo-step" id="qoStep3">
            <div class="qo-success">
                <div class="qo-success__ico"><i class="fa-solid fa-check"></i></div>
                <h3>অর্ডার সফলভাবে গ্রহণ করা হয়েছে! 🎉</h3>
                <p>আপনার অর্ডারটি কনফার্ম হয়েছে। আমাদের প্রতিনিধি শীঘ্রই যোগাযোগ করবেন।</p>
                <div class="qo-success__inv">অর্ডার নম্বর: <b id="qoInvoice">#00000</b></div>
                <div class="qo-success__btns">
                    <a class="qo-next" id="qoOrderLink" href="#" style="text-decoration:none">অর্ডার ট্র্যাক করুন <i class="fa-solid fa-arrow-right"></i></a>
                    <button type="button" class="qo-addcart" onclick="qoClose()" style="height:auto;padding:14px 22px">আরও কেনাকাটা করুন</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    /* ---------- Admin settings থেকে কনফিগ ---------- */
    window.CDQuickOrder = {
        enabled:   {{ $qoEnabled ? 'true' : 'false' }},
        title:     @json($qoTitle),
        confirmText: @json($qoConfirm),
        cartText:  @json($qoCartText),
        cartToast: @json($qoToast),
        endpoint:  "{{ url('quick-order') }}" + '/',
        placeUrl:  "{{ route('quick.order.place') }}",
        customer: {
            name:    @json($qoCustomer ? ($qoCustomer->name ?? '') : ''),
            phone:   @json($qoCustomer ? ($qoCustomer->phone ?? '') : ''),
            address: @json($qoCustomer ? ($qoCustomer->address ?? '') : '')
        }
    };

    if (!window.CDP) window.CDP = {};   // fetched ডাটা ক্যাশ

    var mo  = document.getElementById('qoModal');
    var cd  = { p:null, size:null, color:null, qty:1, price:0, stock:0, shipping:0, cartOnly:false };

    function $(id) { return document.getElementById(id); }
    function qoNum(n) { return Number(n || 0).toLocaleString('en-US'); }

    /* ---------- টোস্ট (দ্রুত নোটিফিকেশন) ---------- */
    function qoToast(msg, err) {
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:' + (err ? '#e11d48' : '#12a150') + ';color:#fff;padding:11px 22px;border-radius:30px;z-index:100000;font-size:13.5px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.2);white-space:nowrap';
        document.body.appendChild(t);
        setTimeout(function(){ t.remove(); }, 2400);
    }
    window.qoToast = qoToast;

    /* ---------- প্রোডাক্ট ডাটা লোড (ক্যাশে না থাকলে fetch) ---------- */
    function qoLoad(id, cb) {
        if (window.CDP && window.CDP[id]) { cb(window.CDP[id]); return; }
        fetch(window.CDQuickOrder.endpoint + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.error) { qoToast('প্রোডাক্ট পাওয়া যায়নি', 1); return; }
                window.CDP[id] = data;
                cb(data);
            })
            .catch(function () { qoToast('সমস্যা হয়েছে, আবার চেষ্টা করুন', 1); });
    }

    /* ---------- স্টেপ নেভিগেশন ---------- */
    function qoGoStep(n) {
        ['qoStep1', 'qoStep2', 'qoStep3'].forEach(function (id, i) { $(id).classList.toggle('on', i === n - 1); });
        var st = [1, 2, 3];
        st.forEach(function (s) {
            $('qoSt' + s).className = 'st' + (s === n ? ' on' : (s < n ? ' done' : ''));
        });
        if (n === 2) qoFillCustomer();
        mo.scrollTop = 0;
        var box = mo.querySelector('.qo-box');
        if (box && box.scrollTo) box.scrollTo({ top: 0 });
    }
    window.qoGoStep = qoGoStep;

    /* ---------- পপআপ খোলা ---------- */
    function qoOpen(id, cartOnly, pre) {
        if (!window.CDQuickOrder.enabled) return false;
        if (!id) return false;
        qoLoad(id, function (p) {
            pre = pre || {};
            cd = {
                p: p,
                size:   pre.size  || null,
                color:  pre.color || null,
                qty:    (parseInt(pre.qty, 10) > 0) ? parseInt(pre.qty, 10) : 1,
                price:  p.price,
                stock:  p.stock,
                shipping: Number(p.shipping || 0),
                cartOnly: !!cartOnly
            };
            $('qoId').value    = p.id;
            $('qoImg').src     = p.img;
            $('qoImgBadge').textContent = '';
            $('qoName').textContent = p.name;
            $('qoLink').href   = p.url;
            $('qoSize').value  = '';
            $('qoColor').value = '';
            $('qoImgBadge').style.display = 'none';

            /* কাস্টমার ইনফো ফিল্ড প্রতি খোলায় রিসেট (লগইন করা কাস্টমারের ডাটা প্রি-ফিল) */
            var cust = window.CDQuickOrder.customer || {};
            $('qoNameInp').value = cust.name || '';
            $('qoPhoneInp').value = cust.phone || '';
            $('qoAddrInp').value = cust.address || '';

            buildChips('qoSizes',  'qoSizeWrap',  p.sizes,  'size',  pre.size);
            buildChips('qoColors', 'qoColorWrap', p.colors, 'color', pre.color);

            /* একটাই অপশন থাকলে অটো সিলেক্ট (কম ক্লিক = বেশি অর্ডার) */
            if (p.sizes.length === 1 && !pre.size)  pick('size',  p.sizes[0].id,  0);
            if (p.colors.length === 1 && !pre.color) pick('color', p.colors[0].id, 0);

            qoSync();
            setQty(cd.qty);

            /* কার্ট-অনলি মোডে সরাসরি কার্ট বাটন */
            if (cd.cartOnly) {
                $('qoAddCartBtn').innerHTML = '<i class="fa-solid fa-cart-plus"></i> ' + window.CDQuickOrder.cartText;
                $('qoNextBtn').style.display = 'none';
                $('qoBtns1').classList.add('qo-btns--single');
            } else {
                $('qoAddCartBtn').innerHTML = '<i class="fa-solid fa-cart-plus"></i> ' + window.CDQuickOrder.cartText;
                $('qoNextBtn').style.display = '';
                $('qoBtns1').classList.remove('qo-btns--single');
            }

            qoGoStep(1);
            mo.classList.add('on');
            document.body.style.overflow = 'hidden';
        });
        return true;
    }
    window.qoOpen = qoOpen;

    /* ---------- চিপ (সাইজ/কালার বাটন) তৈরি ---------- */
    function buildChips(boxId, wrapId, list, type, preId) {
        var box = $(boxId), wrap = $(wrapId);
        box.innerHTML = ''; box.classList.remove('qo-err');
        $(type === 'size' ? 'qoSizePick' : 'qoColorPick').textContent = '';
        if (!list || !list.length) { wrap.style.display = 'none'; return; }
        wrap.style.display = '';

        var selIdx = -1;
        list.forEach(function (o, idx) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'qo-chip';
            b.dataset.id = o.id;
            b.dataset.stock = (type === 'size' && o.has_stock) ? o.stock : '';
            if (type === 'size' && o.has_stock && Number(o.stock) <= 0) b.classList.add('qo-off');
            b.innerHTML = (type === 'color' && o.hex ? '<span class="qo-dot" style="background:' + o.hex + '"></span>' : '') +
                '<span>' + o.name + '</span>' +
                (type === 'size' && o.has_stock ? '<small class="qo-chip-stock">' + (Number(o.stock) > 0 ? o.stock + ' টি' : 'স্টক শেষ') + '</small>' : '');
            b.onclick = function () { if (!b.classList.contains('qo-off')) pick(type, o.id, idx); };
            box.appendChild(b);
            if (preId && String(o.id) === String(preId)) selIdx = idx;
        });
        if (selIdx > -1) pick(type, list[selIdx].id, selIdx);
    }

    /* ---------- সিলেক্ট ---------- */
    function pick(type, id, idx) {
        var box = $(type === 'size' ? 'qoSizes' : 'qoColors');
        [].forEach.call(box.children, function (b, i) { b.classList.toggle('on', i === idx); });
        box.classList.remove('qo-err');
        cd[type] = id;
        $(type === 'size' ? 'qoSize' : 'qoColor').value = id;
        var btnEl = box.children[idx];
        var lbl = '';
        if (btnEl) {
            var span = btnEl.querySelector ? btnEl.querySelector('span') : null;
            lbl = (span ? span.textContent : btnEl.textContent || '').trim();
        }
        $(type === 'size' ? 'qoSizePick' : 'qoColorPick').textContent = '— ' + lbl;
        qoSync();
        setQty(cd.qty);
    }

    /* ---------- ভ্যারিয়েন্ট অনুযায়ী দাম / স্টক ---------- */
    function avail(boxId, fn) {
        var box = $(boxId);
        [].forEach.call(box.children, function (b) {
            var hasStock = !b.dataset.stock || Number(b.dataset.stock) > 0;
            var ok = fn(b.dataset.id) && hasStock;
            b.classList.toggle('qo-off', !ok);
            if (!ok) b.classList.remove('on');
        });
    }
    function qoSync() {
        var p = cd.p, vs = p.variants || [];
        if (vs.length) {
            avail('qoSizes', function (id) {
                return vs.some(function (v) { return v.s == id && (cd.color == null || v.c == null || v.c == cd.color); });
            });
            avail('qoColors', function (id) {
                return vs.some(function (v) { return v.c == id && (cd.size == null || v.s == null || v.s == cd.size); });
            });

            var match = vs.filter(function (v) {
                return (cd.size == null || v.s == null || v.s == cd.size) &&
                       (cd.color == null || v.c == null || v.c == cd.color);
            });
            var chosen = (!p.sizes.length  || cd.size  != null) &&
                         (!p.colors.length || cd.color != null);
            if (chosen && match.length) {
                if (match[0].p > 0) cd.price = match[0].p;
                var stockRows = match.filter(function (v) { return v.st !== null; });
                if (stockRows.length) {
                    cd.stock = (cd.color != null || stockRows.length === 1)
                        ? Number(stockRows[0].st)
                        : stockRows.reduce(function (sum, v) { return sum + Number(v.st); }, 0);
                }
            } else {
                cd.price = p.price;
                cd.stock = p.stock;
            }
        }

        $('qoPrice').textContent = '৳ ' + qoNum(cd.price);
        var oldEl = $('qoOld'), saveEl = $('qoSave');
        if (p.old && p.old > cd.price) {
            oldEl.textContent = '৳ ' + qoNum(p.old);
            oldEl.style.display = '';
            saveEl.textContent = 'সাশ্রয় ৳ ' + qoNum(p.old - cd.price);
            saveEl.style.display = '';
            var pct = Math.round(((p.old - cd.price) * 100) / p.old);
            $('qoImgBadge').textContent = '-' + pct + '%';
            $('qoImgBadge').style.display = '';
        } else { oldEl.style.display = 'none'; saveEl.style.display = 'none'; $('qoImgBadge').style.display = 'none'; }

        var st = $('qoStock');
        if (cd.stock !== null && cd.stock <= 0) {
            st.textContent = '❌ এই ভ্যারিয়েন্টটি স্টকে নেই'; st.style.color = '#e11d48';
        } else if (cd.stock > 0 && cd.stock <= 20) {
            st.textContent = '🔥 তাড়াতাড়ি করুন! মাত্র ' + cd.stock + ' টি বাকি আছে'; st.style.color = 'var(--qo-secondary)';
        } else {
            st.textContent = '✅ স্টকে আছে'; st.style.color = 'var(--qo-green)';
        }
    }

    /* ---------- পরিমাণ ---------- */
    function qoQty(d) { setQty(cd.qty + d); }
    window.qoQty = qoQty;
    function setQty(q) {
        var max = (cd.stock && cd.stock > 0) ? cd.stock : 99;
        cd.qty = Math.max(1, Math.min(q, max));
        $('qoQtyBox').value = cd.qty;
        $('qoQty').value    = cd.qty;
        $('qoTotal').textContent = '৳ ' + qoNum(cd.price * cd.qty);
    }

    function qoClose() { mo.classList.remove('on'); document.body.style.overflow = ''; }
    window.qoClose = qoClose;
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') qoClose(); });

    /* ---------- স্টেপ ১ ভ্যালিডেশন ---------- */
    function qoValidate() {
        var ok = true;
        if (cd.p.sizes.length  && !cd.size)  { flag('qoSizes');  ok = false; }
        if (cd.p.colors.length && !cd.color) { flag('qoColors'); ok = false; }
        if (ok && cd.stock !== null && cd.stock <= 0) { qoToast('প্রোডাক্টটি বর্তমানে স্টকে নেই', 1); ok = false; }
        return ok;
    }
    function flag(id) {
        var b = $(id);
        b.classList.add('qo-err', 'qo-shake');
        setTimeout(function () { b.classList.remove('qo-shake'); }, 420);
        b.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    /* ---------- কাস্টমার তথ্য (সারাংশ আপডেট; ইনপুট qoOpen-এ প্রি-ফিল হয়) ---------- */
    function qoFillCustomer() {
        $('qoSImg').src = cd.p.img;
        $('qoSName').textContent = cd.p.name;
        var opts = [];
        if (cd.size) {
            var sz = (cd.p.sizes || []).find(function (s) { return String(s.id) === String(cd.size); });
            if (sz) opts.push('সাইজ: ' + sz.name);
        }
        if (cd.color) {
            var cl = (cd.p.colors || []).find(function (s) { return String(s.id) === String(cd.color); });
            if (cl) opts.push('কালার: ' + cl.name);
        }
        opts.push('পরিমাণ: ' + cd.qty);
        $('qoSOpts').textContent = opts.join(' • ');
        var sub = cd.price * cd.qty;
        $('qoSSubtotal').textContent = '৳ ' + qoNum(sub);
        $('qoSShipping').textContent = '৳ ' + qoNum(cd.shipping);
        $('qoSGrand').textContent = '৳ ' + qoNum(sub + cd.shipping);
    }

    /* ---------- স্টেপ ২ ভ্যালিডেশন ---------- */
    function qoCustValidate() {
        var ok = true;
        var name = $('qoNameInp').value.trim();
        var phone = $('qoPhoneInp').value.trim();
        var addr = $('qoAddrInp').value.trim();
        ['qoFName', 'qoFPhone', 'qoFAddr'].forEach(function (id) { $(id).classList.remove('err'); });
        if (name.length < 3) { $('qoFName').classList.add('err'); ok = false; }
        if (!/^(\+?88)?01[3-9]\d{8}$/.test(phone.replace(/[\s-]/g, ''))) { $('qoFPhone').classList.add('err'); ok = false; }
        if (addr.length < 5) { $('qoFAddr').classList.add('err'); ok = false; }
        if (!ok) { qoToast('সঠিকভাবে নাম, মোবাইল ও ঠিকানা দিন', 1); return false; }
        return { name: name, phone: phone, address: addr };
    }

    /* ---------- অর্ডার সাবমিট (AJAX → QuickOrderService) ---------- */
    function qoSubmitOrder(e) {
        e.preventDefault();
        if (!qoValidate()) return false;
        var info = qoCustValidate();
        if (!info) return false;

        var btn = $('qoConfirm');
        btn.disabled = true;
        btn.textContent = 'অর্ডার হচ্ছে...';

        var fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        fd.append('id', cd.p.id);
        fd.append('qty', cd.qty);
        fd.append('product_size', cd.size || '');
        fd.append('product_color', cd.color || '');
        fd.append('name', info.name);
        fd.append('phone', info.phone);
        fd.append('address', info.address);
        fd.append('note', '');

        fetch(window.CDQuickOrder.placeUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function (r) {
            return r.json().catch(function () { return { success: false, message: 'সার্ভার সমস্যা হয়েছে' }; });
        })
        .then(function (res) {
            if (!res || res.success !== true) {
                btn.disabled = false;
                btn.textContent = window.CDQuickOrder.confirmText;
                qoToast(res && res.message ? res.message : 'অর্ডারটি করা যায়নি', 1);
                return;
            }
            $('qoInvoice').textContent = '#' + res.invoice_id;
            var link = $('qoOrderLink');
            link.href = res.order_url || '#';
            qoGoStep(3);
            if (typeof window.qoRefreshCartCount === 'function') window.qoRefreshCartCount();
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = window.CDQuickOrder.confirmText;
            qoToast('সমস্যা হয়েছে, আবার চেষ্টা করুন', 1);
        });
        return false;
    }
    window.qoSubmitOrder = qoSubmitOrder;

    /* ---------- স্টেপ ১ → ২ (Next) ---------- */
    $('qoNextBtn').addEventListener('click', function () {
        if (qoValidate()) qoGoStep(2);
    });

    /* ---------- শুধু কার্টে রাখা (রিলোড ছাড়া) ---------- */
    function qoAddCart() {
        if (!qoValidate()) return;
        var fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        fd.append('id', cd.p.id);
        fd.append('qty', cd.qty);
        fd.append('product_size', cd.size || '');
        fd.append('product_color', cd.color || '');

        fetch("{{ route('cart.store') }}", { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
            .then(function (r) { return r.json().catch(function () { return { success: true }; }); })
            .then(function (res) {
                if (res && res.success === false) { qoToast(res.message || 'সমস্যা হয়েছে', 1); return; }
                if (typeof window.qoRefreshCartCount === 'function') window.qoRefreshCartCount();
                qoClose();
                qoToast(window.CDQuickOrder.cartToast);
            })
            .catch(function () { qoToast('সমস্যা হয়েছে, আবার চেষ্টা করুন', 1); });
    }
    window.qoAddCart = qoAddCart;
    $('qoAddCartBtn').addEventListener('click', function () {
        if (cd.cartOnly) { qoAddCart(); return; }
        /* অর্ডার মোডে "কার্টে রাখুন" সরাসরি কার্টে যোগ করবে */
        qoAddCart();
    });

    /* ---------- কার্ট কাউন্ট অ্যাজাক্স আপডেট (নিরাপদ) ---------- */
    function qoRefreshCartCount() {
        fetch("{{ route('cart.count') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var box = document.createElement('div');
                box.innerHTML = html;
                var source = box.querySelector('.cart_count');
                var n = source ? source.textContent.trim() : '';
                if (n === '') return;
                document.querySelectorAll('.cart_count, #cart-qty span, .mobilecart-qty, .js-cart-count').forEach(function (el) {
                    el.textContent = n;
                });
            })
            .catch(function () {});
    }
    window.qoRefreshCartCount = qoRefreshCartCount;

    /* ======================================================================
       গ্লোবাল ইন্টারসেপশন:
       1) cart.store ফর্ম submit → পপআপ খুলবে (details পেজের Order Now)
       2) .qo-order-link / .qo-cart-link <button> ক্লিক → পপআপ খুলবে
       ====================================================================== */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!window.CDQuickOrder || !window.CDQuickOrder.enabled) return;
        if (!form || !form.action || form.action.indexOf('cart.store') === -1) return;
        if (form.classList.contains('qo-form') || form.id === 'qoForm') return;
        /* ক্লাসিক কুইক-ভিউ মডালের নিজস্ব add-to-cart ফর্ম বাদ (ওভারল্যাপ এড়াতে) */
        if (form.closest && form.closest('#custom-modal')) return;

        var idEl = form.querySelector('input[name="id"]');
        var id = idEl ? idEl.value : '';
        if (!id) return;

        var sub = e.submitter || (form.querySelector('button[type=submit]:focus, input[type=submit]:focus'));
        var cartOnly = true;
        if (sub) {
            var cls = String(sub.className || '') + ' ' + String(sub.name || '');
            if ((cls.indexOf('order') > -1 && cls.indexOf('cart') === -1) || sub.name === 'order_now') cartOnly = false;
        }

        /* সাধারণ প্রোডাক্টে (সাইজ/কালার ছাড়া) "Add to Cart" → ক্লাসিক ফ্লোই চলুক (পপআপ নয়) */
        var hasSize  = form.querySelector('input[name="product_size"]');
        var hasColor = form.querySelector('input[name="product_color"]');
        if (cartOnly && !hasSize && !hasColor) return;

        e.preventDefault();
        var pre = {};
        /* রেডিও গ্রুপে checked অপশনটি নিন (প্রথমটা নয়) */
        var sz = form.querySelector('input[name="product_size"]:checked') || form.querySelector('input[name="product_size"]');
        var cl = form.querySelector('input[name="product_color"]:checked') || form.querySelector('input[name="product_color"]');
        var qt = form.querySelector('input[name="qty"]');
        if (sz && sz.value) pre.size = sz.value;
        if (cl && cl.value) pre.color = cl.value;
        if (qt && qt.value) pre.qty = qt.value;

        window.qoOpen(id, cartOnly, pre);
    });

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('.qo-order-link, .qo-cart-link') : null;
        if (!a) return;
        var id = a.getAttribute('data-id');
        if (!id) return;
        e.preventDefault();

        /* পপআপ অ্যাডমিন সেটিংসে বন্ধ থাকলে নিরাপদ ফলব্যাক → প্রোডাক্ট পেজ */
        if (!window.CDQuickOrder || !window.CDQuickOrder.enabled) {
            var url = a.getAttribute('data-url');
            if (url) window.location.href = url;
            return;
        }

        var cartOnly = a.classList.contains('qo-cart-link');
        window.qoOpen(id, cartOnly, {});
    });
})();
</script>
