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
        const parent = $(this).closest('.favorited-product');

        $.get('/get-time-limited-discount-data', {
            productId: productId,
            discountDate: discountDate
        }, (data) => {
            parent.find('.price-discount').text(data.priceDiscount);
            parent.find('.price-normal').text(data.priceNormal);
            parent.find('.date-begin').text(data.dateBegin);
            parent.find('.date-end').text(data.dateEnd);
        });

        return false;
    });
});