/**
 * Timeline for release
 */
var filter_timeline_release = function() {
   $(document).on("click", '.filter_timeline_release li a', function(event) {
      event.preventDefault();
      var _this = $(this);
      //hide all elements in timeline
      $('.filter_timeline_release li a').removeClass('h_active');
      // $('.filterEle').removeClass('h_active');
      $('.h_item').removeClass('h_hidden');
      $('.h_item').addClass('h_hidden');
      $('.ajax_box').empty();
      //activate clicked element
      _this.toggleClass('h_active');

      //find active classname
      var active_classnames = [];
      $('.filter_timeline_release .h_active').each(function() {
         active_classnames.push(".h_content."+$(this).data('type'));
         // $("a[data-type='"+$(this).data('type')+"'].filterEle").addClass('h_active');
      });
      $(active_classnames.join(', ')).each(function(){
         $(this).parent().removeClass('h_hidden');
      });
   });
};
