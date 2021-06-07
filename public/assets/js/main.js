$(function(){
    $(document).on('click', '.discount-wrapper .favorite', function(){
        const productId = $(this).closest('.discount-wrapper').data('id');

        $.post('/toggle-product-favorited-status', {
            productId: productId
        }, (data) => {
            if (data.isFavorited === 1) {
                $(this).addClass('text-warning');
                $(this).html('<i class="fa fa-star fa-2x"></i>');
            } else {
                $(this).removeClass('text-warning');
                $(this).html('<i class="fa fa-star-o fa-2x"></i>');
            }
        }, 'json');

        return false;
    });

    $(document).on('click', '.discount-history .discount-cell', function(){
        const productId = $(this).data('product_id');
        const discountDate = $(this).data('discount_date');
        const parent = $(this).closest('.favorited-product').find('.product-item-wrapper');

        $.get('/get-time-limited-discount-data', {
            productId: productId,
            discountDate: discountDate
        }, (data) => {
            if (data.hasOwnProperty('html')) {
                parent.html(data.html);
            }
        });

        return false;
    });

    $(document).on('click', '.discount-history-wrapper .switch-year', function(e){
        e.preventDefault();
        const productId = $(this).data('product_id');
        const year = $(this).data('year');
        const parent = $(this).closest('.discount-history-wrapper');

        $.get('/get-discount-data-by-year', {
            productId: productId,
            year: year
        }, (data) => {
            if (data.hasOwnProperty('html')) {
                parent.html(data.html);
            }
        }, 'json');

        return false;
    });
});