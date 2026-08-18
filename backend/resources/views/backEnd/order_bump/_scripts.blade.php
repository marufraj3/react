<script>
    (function () {
        var typeSelect = document.getElementById('bump_discount_type');
        var icon = document.getElementById('bump_value_icon');
        if (!typeSelect || !icon) return;

        typeSelect.addEventListener('change', function () {
            icon.innerHTML = this.value === 'percent' ? '%' : '৳';
        });
    })();
</script>
