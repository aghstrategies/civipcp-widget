(function ($) {
  // for testing that its linked
  console.log('linked');

  // AJAX call to print results based on user entered inputs
  var searchingTheMembers = function () {
    console.log($('#cp-name-search').val());
    var mydata = {
      action: 'search_civipcp_names',
      cpnamesearch: $('#cp-name-search').val(),
    };
    $.post(civipcpdir.ajaxurl, mydata, function (response) {
      console.log(response);
      $('#resultsdiv').append(response);
    });
  };

  searchingTheMembers();

  //When user hits enter button remove any previous search info then search again
  $('#dir').click(function (event) {
    event.preventDefault();
    searchingTheMembers();
  });

})(jQuery);
