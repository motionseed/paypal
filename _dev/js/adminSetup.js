/**
 * 2007-2019 PrestaShop
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use
 * International Registered Trademark & Property of PrestaShop SA
 */

const SetupAdmin = {
    init () {
        $('#logoutAccount').on('click', (event) => {
            SetupAdmin.logoutAccount();
        });

        $('#confirmCredentials').click((event) => {
            $(event.currentTarget).closest('form').submit();
        });
    },

    logoutAccount() {
        $.ajax({
            url: controllerUrl,
            type: 'POST',
            data: {
                ajax: true,
                action: 'logOutAccount',
            },
            success(response) {
                if (response.status) {
                    document.location = response.redirectUrl;
                }
            },
        });

    },

};

document.addEventListener('DOMContentLoaded', () => {
    SetupAdmin.init();
});