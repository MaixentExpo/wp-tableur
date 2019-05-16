/**
 * Fonctions jQuery du plugin
 */
jQuery(document).ready(function ($) {
    // console.log('plugin wp-tableur OK');

    if ($("#form-tableur").length) {
        // console.log("search");
        if ($("#search_id-search-input").length) {
            var search_string = $("#search_id-search-input").val();
            // console.log(search_string);
            if (search_string != "") {
                $("#form-tableur .pagination-links a").each(function () {
                    this.href = this.href + "&s=" + search_string;
                });
            }
        }
    }

    // $('.editable-cancel').each(function(index, elem) {
    //     // $(elem).css('text-decoration', 'none');
    //     $(elem).html('Annuler');
    // });

    $.fn.editable.defaults.mode = 'inline'; // inline or popup
    $.fn.editable.defaults.showbuttons = 'bottom'; // left(true)|bottom|false
    $.fn.editableform.buttons = '<button type="submit" class="editable-submit">OK</button>'+
    '<button type="button" class="editable-cancel">Annuler</button>';
    $('.tbr-editable').editable({
        url: ajaxurl,
        ajaxOptions: {
            type: 'post',
            dataType: 'json'
        },
        params: function(params) {
            // ajout de l'action attendue par le hook wp_ajax
            params.action = "tbr_x_editable";
            params.page = Tableur.page;
            params.security = Tableur.ajax_nonce;
            return params;
        },
        success: function(response, newValue) {
            // console.log(response.data);
            if ( $(this).data("refresh") ) {
                document.location.reload();
            }
        },
        error: function(response) {
            // console.log(response.statusText);
            return response.statusText;
        }
    });
    $('.tbr-editable-items').editable({
        url: ajaxurl,
        ajaxOptions: {
            type: 'post',
            dataType: 'json'
        },
        // source: ajaxurl, est fourni directement dans le html
        sourceOptions: { // pour récupérer les items de la liste
            data: {
                action: 'tbr_get_items',
                page: Tableur.page,
                security: Tableur.ajax_nonce,
            },
            type: 'post'
        },
        params: function(params) {
            // ajout de l'action attendue par le hook wp_ajax
            params.action = "tbr_x_editable";
            params.page = Tableur.page;
            params.security = Tableur.ajax_nonce;
            return params;
        },
        success: function(response, newValue) {
            // console.log(response.data);
            if ( $(this).data("refresh") ) {
                document.location.reload();
            }
        },
        error: function(response) {
            // console.log(response.statusText);
            return response.statusText;
        }
    });

    $(document).on('click', '.tbr-editable-checkbox', function(event) {
		var $value = $(this).attr("checked") ? '1' : '0';
		var $refresh = $(this).data("refresh") ? true : false;
		var $data = {};
		$data['action'] = 'tbr_x_editable'; 
        $data['page'] = Tableur.page; 
        $data['security'] = Tableur.ajax_nonce;
        $data['name'] = $(this).attr("name");
        $data['value'] = $value;
        $data['pk'] = $(this).data("id");
		$.ajax({
			url: ajaxurl,
			type: 'post',
			data: $data,
            dataType: "json",
			success:function(response) {
                // console.log(response.data);
                if ( $refresh ) {
                    document.location.reload();
                }
            },
			error:function(response) {
                return response.statusText;
			}
		});
    });
    
});

