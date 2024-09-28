var GoogleCharts = {
    NoPrintLink: false,
    Init: function (callback) { // requires https://www.gstatic.com/charts/loader.js
        google.charts.load('current', {packages: ['corechart']});
        google.charts.setOnLoadCallback(callback);
    },
    InitJSAPI: function (callback) { // WebkitHTMLToPDF requires https://www.google.com/jsapi
        google.load("visualization", "1.1", {
            packages: ["corechart"],
            callback: callback
        });
    },

    ComboChart: function (title, data, element_id, options) {
        if (data.length < 2) {
            return;
        }

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.ComboChart(document.getElementById(element_id));

        $('#' + element_id).after('<div style="text-align: center;" id="' + element_id + '_png"></div>');

        google.visualization.events.addListener(chart, 'ready', function () {
            if (!GoogleCharts.NoPrintLink) {
                document.getElementById(element_id + '_png').innerHTML = '<a target="_blank" href="' + chart.getImageURI() + '">Printable version</a>';
            }
        });

        chart.draw(google.visualization.arrayToDataTable(data), options);
    },

    LineChart: function (title, data, element_id, width, height, legend) {
        if (!legend) {
            legend = 'none';
        }

        let options = {
            title: title,
            width: width,
            height: height,
            legend: {position: legend}
        };

        if (data.length < 2) {
            return;
        }

        // Instantiate and draw our chart, passing in some options.
        let chart = new google.visualization.LineChart(document.getElementById(element_id));

        $('#' + element_id).after('<div style="text-align: center;" id="' + element_id + '_png"></div>');

        google.visualization.events.addListener(chart, 'ready', function () {
            if (!GoogleCharts.NoPrintLink) {
                document.getElementById(element_id + '_png').innerHTML = '<a target="_blank" href="' + chart.getImageURI() + '">Printable version</a>';
            }
        });

        chart.draw(google.visualization.arrayToDataTable(data), options);
    },

    ColumnChart: function (title, data, element_id, width, height, legend, hide_print) {
        if (!legend) {
            legend = 'none';
        }

        let options = {
            title: title,
            width: width,
            height: height,
            legend: {position: legend}
        };

        if (data.length < 2) {
            return;
        }

        // Instantiate and draw our chart, passing in some options.
        let chart = new google.visualization.ColumnChart(document.getElementById(element_id));

        if (!hide_print) {
            $('#' + element_id).after('<div style="text-align: center;" id="' + element_id + '_png"></div>');


            google.visualization.events.addListener(chart, 'ready', function () {
                if (!GoogleCharts.NoPrintLink) {
                    document.getElementById(element_id + '_png').innerHTML = '<a target="_blank" href="' + chart.getImageURI() + '">Printable version</a>';
                }
            });
        }

        chart.draw(google.visualization.arrayToDataTable(data), options);
    },

    PieChart: function (title, data, element_id, width, height, show_legend) {
        let options = {
            title: title,
            width: width,
            height: height,
            legend: show_legend ? true : {position: 'none'}
        };

        if (data.length < 2) {
            return;
        }

        // Instantiate and draw our chart, passing in some options.
        let chart = new google.visualization.PieChart(document.getElementById(element_id));

        $('#' + element_id).after('<div style="text-align: center;" id="' + element_id + '_png"></div>');

        google.visualization.events.addListener(chart, 'ready', function () {
            if (!GoogleCharts.NoPrintLink) {
                document.getElementById(element_id + '_png').innerHTML = '<a target="_blank" href="' + chart.getImageURI() + '">Printable version</a>';
            }
        });

        chart.draw(google.visualization.arrayToDataTable(data), options);
    },

    PercentChart: function (percent_complete, element_id, width, height, pie_hole) {
        if (!pie_hole) {
            pie_hole = 0.7;
        }

        let data = [
            ['c', 'p']
            , ['c', percent_complete]
            , ['nc', 100 - percent_complete]

        ];

        let options = {
            pieSliceBorderColor: "transparent",
            backgroundColor: {fill: 'transparent'},
            pieSliceText: "none",
            colors: ['#067ab5', '#ffffff'],
            title: '',
            width: width,
            height: height,
            legend: {position: 'none'},
            pieHole: pie_hole,
            tooltip: {
                trigger: 'none'
            }
        };

        // Instantiate and draw our chart, passing in some options.
        let chart = new google.visualization.PieChart(document.getElementById(element_id));

        chart.draw(google.visualization.arrayToDataTable(data), options);
    }

};