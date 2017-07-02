/**
 * A76支付组件，负责弹出窗口，显示支付页面
 * @author 尖刀 <a761208@gmail.com>
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

                var url = this.href;
                var pay_params = pay_init();
                $.each(pay_params, function(index, value) {
                    url += "&" + index + "=" + value;
                });

                if (options['popup'] !== false) {
                    // 通过弹出小窗口支付
                    payPopup($container, options, this, url);
                } else {
                    payIframe($container, url);
                }
            });
        });
    };
});
/**
 * 弹出窗口支付
 * @param $container
 * @param options
 * @param obj
 * @param url
 */
function payPopup($container, options, obj, url) {
    var payChoicePopup = $container.data('payChoicePopup');
    if (payChoicePopup) {
        payChoicePopup.close();
    }

    var popupOptions = $.extend({}, options.popup); // clone
    var localPopupWidth = obj.getAttribute('data-popup-width');
    if (localPopupWidth) {
        popupOptions.width = localPopupWidth;
    }
    var localPopupHeight = obj.getAttribute('data-popup-height');
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
}
/**
 * 通过嵌入iframe支付
 * @param $container
 * @param url
 */
function payIframe($container, url) {
    var iframe = '<iframe id="yii_pay_choice" src="' + url + '" style="border:none; position:absolute; left:0; top:0; width:100%; height:' + $(document).height() + 'px; background:#FFF;"></iframe>';
    $container.append(iframe);
}
/**
 * 检查支付结果并回调
 * @param url string 检查结果的地址
 * @param params 提交参数
 * @returns
 */
function checkPayResult(url, params) {
    $.getJSON(url, params, function(json) {
        if (json['result'] === 'success') { // 返回结果正常
            if (json['pay_result'] === 'success') { // 支付成功
                if (window.opener && !window.opener.closed) {
                    // 弹出窗口
                    window.opener.pay_callback(json);
                    window.opener.focus();
                    window.close();
                } else {
                    window.parent.pay_callback(json);
                    $(window.parent.document.getElementById('yii_pay_choice')).remove();
                }
                return true;
            }
        }
        window.setTimeout(function() {checkPayResult(url, params);}, 1000);
    });
}
