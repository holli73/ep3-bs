(function() {

    $(document).ready(function() {

        var urlProvider = $("#puf-url-provider");

        var userInput = $("#puf-user");

        userInput.autocomplete({
            "minLength": 1,
            "source": urlProvider.data("user-autocomplete-url")
        });

    });

})();
