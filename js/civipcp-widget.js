(function ($) {
  // for testing that its linked
  console.log('linked');

  // AJAX call to print results based on user entered inputs
  var searchingTheMembers = function () {
    console.log($('#cp-name-search').val());
    var mydata = {
      action: 'search_civipcp_names',
      cpnamesearch: $('#cp-name-search').val(),
      cpparams: civipcpdir.params,
    };
    $.post(civipcpdir.ajaxurl, mydata, function (response) {
      if ($('#resultsdiv').length == 1) {
        console.log($('#resultsdiv').length);

        $('#resultsdiv').remove();
      }

      $('.post-filter').append(response);
    });
  };

  searchingTheMembers();

  //When user hits enter button remove any previous search info then search again
  $('#dir').click(function (event) {
    event.preventDefault();
    searchingTheMembers();
  });

})(jQuery);
