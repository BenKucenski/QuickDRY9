let Strings = {
    /**
     *
     * @param date
     * @returns {string}
     * @constructor
     */
    DateToSQLDate: function (date) {
        return date.toLocaleString('en-US', {year: 'numeric', month: 'numeric', day: 'numeric'});
    },

    // https://stackoverflow.com/questions/5619202/converting-string-to-date-in-js
    stringToDate: function (_date, _format, _delimiter) {
        //stringToDate("17/9/2014","dd/MM/yyyy","/");
        //stringToDate("9/17/2014","mm/dd/yyyy","/");
        //stringToDate("9-17-2014","mm-dd-yyyy","-");

        let formatLowerCase = _format.toLowerCase();
        let formatItems = formatLowerCase.split(_delimiter);
        let dateItems = _date.split(_delimiter);
        let monthIndex = formatItems.indexOf("mm");
        let dayIndex = formatItems.indexOf("dd");
        let yearIndex = formatItems.indexOf("yyyy");
        let month = parseInt(dateItems[monthIndex]);
        month -= 1;
        return new Date(dateItems[yearIndex], month, dateItems[dayIndex]);
    },
    InitTinyMCE: function (elem_id) {
        tinymce.init({
            selector: '#' + elem_id,
            height: 500,
            theme: 'modern',
            plugins: [
                'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                'searchreplace wordcount visualblocks visualchars code fullscreen',
                'insertdatetime media nonbreaking save table contextmenu directionality',
                'emoticons template paste textcolor colorpicker textpattern imagetools'
            ],
            toolbar1: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
            toolbar2: 'print preview media | forecolor backcolor emoticons',
            image_advtab: true,
            templates: [],
            content_css: [
                '//fast.fonts.net/cssapi/e6dc9b99-64fe-4292-ad98-6974f93cd2a2.css',
                '//www.tinymce.com/css/codepen.min.css'
            ]
        });
    },
    formatNumber: function (number) {
        let results = '';
        let numbers = number.replace(/\D/g, '');
        let char = {0: '(', 3: ') ', 6: '-'};
        this.value = '';
        for (let i = 0; i < numbers.length; i++) {
            results += (char[i] || '') + numbers[i];
        }

        return results;
    },
    //http://stackoverflow.com/questions/280634/endswith-in-javascript
    endsWith: function (str, suffix) {
        return str.indexOf(suffix, str.length - suffix.length) !== -1;
    },

    trim: function (stringToTrim) {
        return stringToTrim.replace(/^\s+|\s+$/g, "");
    },

    ltrim: function (stringToTrim) {
        return stringToTrim.replace(/^\s+/, "");
    },

    rtrim: function (stringToTrim) {
        return stringToTrim.replace(/\s+$/, "");
    },

    twoDigits: function (d) {
        if (0 <= d && d < 10) return "0" + d.toString();
        if (-10 < d && d < 0) return "-0" + (-1 * d).toString();
        return d.toString();
    },

    /**
     *
     * @returns {string}
     * @constructor
     */
    Timestamp: function () {
        let d = new Date();
        return Strings.twoDigits(1 + d.getMonth()) + "/" + Strings.twoDigits(d.getDate()) + "/" + d.getFullYear() + " " + Strings.twoDigits(d.getHours()) + ":" + Strings.twoDigits(d.getMinutes()) + ":00";
    },
    money2num: function (strMoney) {
        if (!strMoney) {
            return "";
        }

        //var newnum = strMoney.replace("$","").replace(",","");
        let newnum = strMoney.replace("$", "").replace(/[',]/gi, '');
        if (!isNaN(newnum))
            return (newnum);
        else
            return "";
    },


    num2money: function (n_value, prefix, elementId, dec, retval) {
        dec = (dec) ? dec : false;
        let oNval = n_value.toString();
        let elem = null;

        if (typeof (n_value) === "string") {
            n_value = money2num(n_value);
        }


        if (n_value === "") {
            if (elementId != null) {
                elem = $("#" + elementId);
                if (elem.type === "text") {
                    if (retval) {
                        elem.val("");
                    } else {
                        elem.val(prefix + "0");
                    }
                } else
                    elem.html(prefix + "0");
            }
            return;
        }
        let pre = (!prefix) ? "$" : prefix;

        if (isNaN(Number(n_value)))
            return 'ERROR';

        let b_negative = Boolean(n_value < 0);
        n_value = Math.abs(n_value);

        // ROUND TO 1/100 PRECISION, ADD ENDING ZEROES IF NEEDED
        let roundPt = null;
        if (dec && dec > 2) {
            dec = parseInt(oNval.substr(oNval.indexOf('.')).length - 1);
            let divd = parseInt(eval('1e' + dec));
            roundPt = (Math.round(n_value * divd) % divd > 9) ? (Math.round(n_value * divd) % divd) : ('0' + Math.round(n_value * divd) % divd);
        } else {
            roundPt = (Math.round(n_value * 1e2) % 1e2 > 9) ? (Math.round(n_value * 1e2) % 1e2) : ('0' + Math.round(n_value * 1e2) % 1e2);
        }
        let s_result = String(roundPt + '00').substring(0, dec);
        // SEPARATE ALL ORDERS
        let b_first = true;
        let s_subresult;
        while (n_value >= 1) {
            s_subresult = (n_value >= 1e3 ? '00' : '') + Math.floor(n_value % 1e3);
            s_result = s_subresult.slice(-3) + (b_first ? '.' : ',') + s_result;
            b_first = false;
            n_value = n_value / 1e3;
        }

        // ADD AT LEAST ONE INTEGER DIGIT
        if (b_first)
            s_result = '0.' + s_result;

        // APPLY FORMATTING AND RETURN
        if (!dec) {
            s_result = s_result.substring(0, s_result.indexOf("."));
        }
        if (elementId != null) {
            elem = $("#" + elementId);
            if (elem.type === "text") {
                elem.val(b_negative ? '-' + pre + s_result + '' : pre + s_result);
            } else {
                elem.html(b_negative ? '-' + pre + s_result + '' : pre + s_result);
            }
        }
        return b_negative
            ? '-' + pre + s_result + ''
            : pre + s_result;
    },
    chkOnlyEmailIsValid: function (sEmail) {
        let filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
        return filter.test(sEmail);
    }
};

if (!String.prototype.startsWith) {
    String.prototype.startsWith = function (searchString, position) {
        position = position || 0;
        return this.substr(position, searchString.length) === searchString;
    };
}

