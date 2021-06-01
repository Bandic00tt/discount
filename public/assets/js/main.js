$(function(){
    $(document).on('click', '.discount-wrapper .favorite', function(){
        const productId = $(this).closest('.discount-wrapper').data('id');

        $.post('/favorite-product', {
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
});