/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



(function ($) {

    // next step click from sif pop

    // gravity add to document button clicked 
    $("#esig-insert-woo-tag").click(function () {

        //var form_id= $('input[name="esig_gf_form_id"]').val() ;

        var tagValue = $('select[name="esig-woocommerce-tag"]').val();
        // 
        var return_text = '{{' + tagValue + '}}';
        esig_sif_admin_controls.insertContent(return_text);

        tb_remove();
    });


    $('#select-gravity-form-list').click(function () {
        $(".chosen-drop").show(0, function () {
            $(this).parents("div").css("overflow", "visible");
        });

    });




})(jQuery);
