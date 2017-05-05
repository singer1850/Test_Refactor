/*delete elements popupmenu*/
var RefactorMenu = Class.create({
    initialize: function (url, title) {
        this.winUrl = url;
        this.winTitle = title;
        this.formId = 'options-refactor-form';
    },
    closePopupMenu: function (items) {
        if (Array.isArray(items)) {
            for (var i = 0; i < items.length; i++) {
                if ($(items[i]) != null) {
                    $(items[i]).remove();
                }
            }
        } else {
            $(items).remove();
        }
    },
    initializeMenu: function () {
        var self = this;
        $$('.order-tables').each(function(elem) {
            elem.on('contextmenu', function (event) {
                var coordX = Event.pointerX(event);
                var coordY = Event.pointerY(event);
                if ($('custom-option-popup-menu') == null) {
                    self.win = null;
                    self.openPopupMenu(coordX, coordY);
                } else {
                    self.win = null;
                    self.closePopupMenu(['custom-option-popup-menu','custom-option-popup-menu-item']);
                    self.openPopupMenu(coordX, coordY);
                }
                event.preventDefault();
            });
        });
    },
    resizeWindow: function () {
        var selfwin = this.win;
        var inputs = $$('#options-refactor input:not([type=hidden])');
        var selects = $$('#options-refactor select:not(.datetime)');
        var dtSimplePickers = $$('#options-refactor .datetime.picker-simple');
        var dtIncreasedPickers = $$('#options-refactor .datetime.picker-increased');
        var customNotices = $$('#options-refactor .notice-fild');
        var inputFiles = $$('#options-refactor input[type="file"]');
        if (inputs.length < 2) {
            selfwin.height = 150;
        } else {
            selfwin.height = 200;
            if (inputs.length > 2) {
                for (var i = 0; i < inputs.length; i++) {
                    selfwin.height += 10;
                }
            }
        }
        for (var i = 0; i < selects.length; i++) {
            selfwin.height += (i == 0) ? 30 : 20;
        }
        for (var i = 0; i < customNotices.length; i++) {
            selfwin.height += 10;
        }
        for (var i = 0; i < dtSimplePickers.length; i++) {
            if (!((i + 1) % 3)) {
                selfwin.height += 26;
            }
            continue;
        }
        for (var i = 0; i < inputFiles.length; i++) {
            selfwin.height += 5;
        }
        selfwin.width += ((dtIncreasedPickers.length > 0) || (inputFiles.length > 0)) ? 55 : 0;
        for (var i = 0; i < dtIncreasedPickers.length; i++) {
            if (!(i % 5)) {
                selfwin.height += 26;
            }
            continue;
        }
    },
    openWindow: function () {
        var selfmenu = this;
        this.win = new Window({
            title: selfmenu.winTitle,
            closable:true,
            className:'magento',
            windowClassName:'popup-window',
            hideEffect:Element.hide,
            showEffect:Element.show,
            id:'widget-chooser',
            zIndex:10000,
            destroyOnClose: true,
            recenterAuto:false,
            resizable: false,
            minWidth:250,
            minimizable: false,
            maximizable: false,
            draggable: true
        });

        this.win.refactorForm = null;
        var selfwin = this.win;
        this.win.setAjaxContent(
            selfmenu.winUrl,
            {
                method: 'post',
                parameters: '',
                onComplete: function () {
                    if ($('options-refactor')) {
                        selfwin.refactorForm = new VarienForm(selfmenu.formId, true);

                        Validation.add('validate-date-day', 'Please enter a valid day of date', function (field_value) {
                            var month = $$('.validate-date-month');
                            var year = $$('.validate-date-year');
                            var leapYear = false;
                            if ( !(year.first().value % 400) ) {
                                leapYear = true;
                            } else {
                                if ((!(year.first().value % 4)) && (year.first().value % 100)) {
                                    leapYear = true;
                                }
                            }
                            if ( ((month.first().value == 4) || (month.first().value == 6) || (month.first().value == 9)
                                || (month.first().value == 11)) && (field_value > 30) ) {
                                return false;
                            }
                            if ( (month.first().value == 2) && (((field_value > 28) && !(leapYear)) || ((field_value > 29) && (leapYear))) ) {
                                return false;
                            }
                            return true;
                        });

                        selfmenu.resizeWindow();

                        $('options-changes-cancel').on('click', function (event) {
                            selfwin.close();
                            event.preventDefault();
                        });
                        $('options-refactor-form').on('submit', function (event) {
                            if (selfwin.refactorForm.validator.validate()) {
                                $('options-changes-submit').writeAttribute('disabled', 'true');
                                if ($$('#' + selfmenu.formId + ' input[type="file"]').length != 0) {
                                    if (window.FormData) {
                                        selfmenu.sendOptionsWithFile(event);
                                    } else {
                                        alert('Warning: You using old version browser. Please update it');
                                    }
                                } else {
                                    selfmenu.sendOptions();
                                }
                            }
                            event.preventDefault();
                        });

                    }
                }
            },
            true,
            true
        );
    },
    openPopupMenu: function (x, y) {
        $('html-body').insert({
            bottom: new Element('ul', {
                id: 'custom-option-popup-menu',
                style: 'top: ' + y + 'px; left: ' + x + 'px;',
            })
        });
        $('custom-option-popup-menu').insert({
            bottom: new Element('li', {
                id: 'custom-option-popup-menu-item',
            })
        });
        var self = this;
        $('custom-option-popup-menu-item').update('Open Custom Options Editor');
        $('custom-option-popup-menu-item').on('click', function () {
            self.openWindow();
            self.closePopupMenu(['custom-option-popup-menu','custom-option-popup-menu-items']);
        });
    },

    acceptionResponse: function (responseText, pointerMenu) {
        var resultArray = responseText.evalJSON(true);
        if ( (resultArray['message_errors'] != null) && (resultArray['message_errors'] != '') ) {
            alert(resultArray['message_errors']);
        } else {
            if (resultArray['message_html']) {
                var blockItems = $$('#sales_order_view_tabs_order_info_content > div > div.grid.np');
                blockItems[0].outerHTML = resultArray['message_html'];
                var endBr = $$('#sales_order_view_tabs_order_info_content > div > br');
                endBr[0].remove();
                pointerMenu.initializeMenu();
            }
            if ( (resultArray['message_warning'] != null) && (resultArray['message_warning'] != '') ) {
                for (var i = 0; i < resultArray['message_warning'].length; i++) {
                    alert(resultArray['message_warning'][i]);
                }
            }
        }
    },

    sendOptions: function () {
        var selectedForm = $(this.formId);
        var msg = selectedForm.serialize();
        var self = this;
        var vAjax = new Ajax.Request(
            selectedForm.readAttribute('action'),
            {
                method: 'post',
                parameters: msg,
                onSuccess: function (responce) {
                    self.acceptionResponse(responce.responseText, self);
                },
                onFailure: function () {
                    alert('Uncaught error: response is not reseived');
                }
            }
        );
        this.win.close();
    },
    sendOptionsWithFile: function (e) {
        var self = this;
        var form = e.target;
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status === 200)
            {
                self.win.enable = false;
                self.acceptionResponse(xhr.responseText, self);
                self.win.close();
            }
        };
        xhr.open('POST', form.action + '?isAjax=true');
        xhr.send(formData);
    },
});
