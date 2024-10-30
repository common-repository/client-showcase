jQuery(document).ready(function($) {
  jQuery('.client-showcase-list').sortable({
    items: '.list_item',
    opacity: 0.6,
    cursor: 'move',
    axis: 'y',
    update: function() {
      var order = jQuery(this).sortable('serialize')+'&action=client_showcase_update_order';
      jQuery.post(ajaxurl, order, function(response){
        //console.log(order);
      });
    }
  });
});