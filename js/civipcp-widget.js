(function ($) {
  // for testing that its linked
  console.log('linked');

  // AJAX call to print results based on user entered inputs
  var searchingTheMembers = function ($offset) {
    console.log($('#cp-name-search').val());
    var mydata = {
      action: 'search_civipcp_names',
      cpnamesearch: $('#cp-name-search').val(),
      cpparams: civipcpdir.params,
      cpoffset: $offset,
    };
    $.post(civipcpdir.ajaxurl, mydata, function (response) {
      if ($('#resultsdiv').length == 1) {
        console.log($('#resultsdiv').length);

        $('#resultsdiv').remove();
      }

      $('.post-filter').append(response);
    });
  };

  searchingTheMembers(0);

  //When user hits enter button remove any previous search info then search again
  $('#dir').click(function (event) {
    var $offset = 0;
    event.preventDefault();
    searchingTheMembers($offset);
  });

  $('#clear').click(function (event) {
    var $offset = 0;
    $('#cp-name-search').val('');
    event.preventDefault();
    searchingTheMembers($offset);
  });

  $('a.page').click(function (event) {
    $('#cp-name-search').val('');
    event.preventDefault();
    var $page = $(this).attr('id');
    var $offset = (parseInt($page) * 10) - 10;
    searchingTheMembers($offset);
  });

})(jQuery);
