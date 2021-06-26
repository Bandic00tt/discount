$(function(){
    function viewCities(data) {
        if (data.hasOwnProperty('html')) {
            let modal = $('#selectCityModal');
            modal.find('.cities-list-modal').html(data.html);

            const selectCityModal = new bootstrap.Modal(modal, {keyboard: false});
            selectCityModal.show();
        }
    }

    const locationId = Cookies.get('discountLocationId');
    if (locationId === undefined) {
        $.get('/cities-partial', viewCities, 'json');
    }

    $(document).on('click', '.select-city', function (e){
        e.preventDefault();
        $.get('/cities-partial', viewCities, 'json');
        return false;
    });

    $(document).on('keyup', '#search-cities-input', function (e) {
        e.preventDefault();
        const query = $(this).val();
        const form = $(this).closest('form');
        const url = form.attr('action');

        if (query.length > 1) {
            $.get(url, {query: query}, (data) => {
                if (data.hasOwnProperty('html')) {
                    let modal = $('#selectCityModal');
                    modal.find('.cities-list-modal').html(data.html);
                }
            }, 'json');
        } else {
            $.get(url, (data) => {
                if (data.hasOwnProperty('html')) {
                    let modal = $('#selectCityModal');
                    modal.find('.cities-list-modal').html(data.html);
                }
            }, 'json');
        }

        return false;
    });

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
        const parent = $(this).closest('.product-block').find('.product-item-wrapper');

        $.get('/time-limited-discount-data', {
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
        const url = $(this).attr('href');
        const productId = $(this).data('product_id');
        const year = $(this).data('year');
        const parent = $(this).closest('.discount-history-wrapper');

        $.get(url, {
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