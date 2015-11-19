var titles = {
    "success": "Success"
};


$(document).ready(function() {
    $('select').select2({
        minimumResultsForSearch: 8
    });

    var stacks = {
        stack_top_right: {
            "dir1": "down",
            "dir2": "left",
            "push": "top",
            "spacing1": 10,
            "spacing2": 10
        },
        stack_top_left: {
            "dir1": "down",
            "dir2": "right",
            "push": "top",
            "spacing1": 10,
            "spacing2": 10
        },
        stack_bottom_left: {
            "dir1": "right",
            "dir2": "up",
            "push": "top",
            "spacing1": 10,
            "spacing2": 10
        },
        stack_bottom_right: {
            "dir1": "left",
            "dir2": "up",
            "push": "top",
            "spacing1": 10,
            "spacing2": 10
        },
        stack_bar_top: {
            "dir1": "down",
            "dir2": "right",
            "push": "top",
            "spacing1": 0,
            "spacing2": 0
        },
        stack_bar_bottom: {
            "dir1": "up",
            "dir2": "right",
            "spacing1": 0,
            "spacing2": 0
        },
        stack_context: {
            "dir1": "down",
            "dir2": "left",
            "context": $("#stack-context")
        }
    };

    $('.notification-handler').each(function(index, element) {
        element = $(element);

        var noteStyle = element.data('style');
        var noteShadow = true;
        var noteOpacity = 1;
        var noteStack = "stack_top_right";

        // Create new Notification
        new PNotify({
            title: titles[element.data('type')],
            text: element.html(),
            shadow: noteShadow,
            opacity: noteOpacity,
            addclass: noteStack,
            type: noteStyle,
            stack: stacks[noteStack],
            width: findWidth(noteStack),
            delay: 1400
        });

    });

    $('.js-autocomplete').each(function(){
        var queryParams = $(this).data('params');

        $(this).select2({
            ajax: {
                url: $(this).data('url'),
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    queryParams.query = params.term;

                    return queryParams;
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });
    });


    var removeLink = null;
    $('.js-remove-confirm').click(function (e) {
        console.log('Click');
        console.log(e);

        if (!$(this).hasClass('js-ignore-confirm')) {
            e.preventDefault();
            openPopup('#confirm-popup');
            removeLink = $(this);

            return false;
        }
    });

    $(document).on('click', '.js-close-popup', function (e) {
        e.preventDefault();

        removeLink = null;
        closePopup();
    });

    $(document).on('click', '.js-confirm-action', function (e) {
        e.preventDefault();

        if (removeLink != null) {
            setTimeout(function(){
                var myEvt = document.createEvent('MouseEvents');
                myEvt.initEvent('click', true, true);
                removeLink.addClass('js-ignore-confirm').get(0).dispatchEvent(myEvt);
            }, 100);
        }

        closePopup();
    });
});

$.magnificPopup.instance._onFocusIn = function(e) {
    // Do nothing if target element is select2 input
    if( $(e.target).hasClass("select2-search__field") ) {
        return true;
    }
    // Else call parent method
    $.magnificPopup.proto._onFocusIn.call(this,e);
};

function closePopup() {
    $.magnificPopup.instance.close();
}

function openPopup(popupId) {
    $.magnificPopup.open({
        removalDelay: 500,
        items: {
            src: popupId
        },
        callbacks: {
            beforeOpen: function () {
                this.st.mainClass = 'mfp-flipInY';
            },
            open: function () {

            }
        },
        midClick: true
    });
}

function showNotify(type, text)
{

    new PNotify({
        title: titles[type],
        text: text,
        shadow: true,
        opacity: 1,
        addclass: "stack_top_right",
        type: type,
        stack: {
            "dir1": "down",
            "dir2": "left",
            "push": "top",
            "spacing1": 10,
            "spacing2": 10
        },
        width: findWidth("stack_top_right"),
        delay: 1400
    });
}

function findWidth(noteStack) {
    if (noteStack == "stack_bar_top") {
        return "100%";
    }
    if (noteStack == "stack_bar_bottom") {
        return "70%";
    } else {
        return "320px";
    }
}