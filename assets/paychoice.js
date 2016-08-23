/**
 * A76 pay choice widget.
 *
 * This is the JavaScript widget used by the a76\pay\widgets\PayChoice widget.
 */
jQuery(function($) {
    $.fn.paychoice = function(options) {
        options = $.extend({
            triggerSelector: 'a.pay-link',
            popup: {
                resizable: 'yes',
                scrollbars: 'no',
                toolbar: 'no',
                menubar: 'no',
                location: 'no',
                directories: 'no',
                status: 'yes',
                width: 450,
                height: 380
            }
        }, options);

        return this.each(function() {
            var $container = $(this);

            $container.find(options.triggerSelector).on('click', function(e) {
                e.preventDefault();

                var payChoicePopup = $container.data('payChoicePopup');

                if (payChoicePopup) {
                	payChoicePopup.close();
                }

                var url = this.href;
                var pay_params = pay_init();
                $.each(pay_params, function(index, value) {
                    url += "&" + index + "=" + value;
                });
                var popupOptions = $.extend({}, options.popup); // clone

                var localPopupWidth = this.getAttribute('data-popup-width');
                if (localPopupWidth) {
                    popupOptions.width = localPopupWidth;
                }
                var localPopupHeight = this.getAttribute('data-popup-height');
                if (localPopupWidth) {
                    popupOptions.height = localPopupHeight;
                }

                popupOptions.left = (window.screen.width - popupOptions.width) / 2;
                popupOptions.top = (window.screen.height - popupOptions.height) / 2;

                var popupFeatureParts = [];
                for (var propName in popupOptions) {
                    if (popupOptions.hasOwnProperty(propName)) {
                        popupFeatureParts.push(propName + '=' + popupOptions[propName]);
                    }
                }
                var popupFeature = popupFeatureParts.join(',');

                payChoicePopup = window.open(url, 'yii_pay_choice', popupFeature);
                payChoicePopup.focus();

                $container.data('payChoicePopup', payChoicePopup);
            });
        });
    };
});
