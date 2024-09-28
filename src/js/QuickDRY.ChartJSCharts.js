// <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
// <body onload="if (typeof InitChartJS === 'function') { ChartJSCharts.Init(InitChartJS); }">

var ChartJSCharts = {
    Init: function (callback) {
        // wkhtmltopdf 0.12.5 crash fix.
        // https://github.com/wkhtmltopdf/wkhtmltopdf/issues/3242#issuecomment-518099192
        'use strict';
        (function (setLineDash) {
            CanvasRenderingContext2D.prototype.setLineDash = function () {
                if (!arguments[0].length) {
                    arguments[0] = [1, 0];
                }
                // Now, call the original method
                return setLineDash.apply(this, arguments);
            };
        })(CanvasRenderingContext2D.prototype.setLineDash);
        Function.prototype.bind = Function.prototype.bind || function (thisp) {
            var fn = this;
            return function () {
                return fn.apply(thisp, arguments);
            };
        };

        if (typeof (callback) == 'function') {
            callback();
        }
    },
    DrawLegend: function (chart) {
        var text = [];
        text.push('<ul class="chart_legend">');
        for (var i = 0; i < chart.data.datasets.length; i++) {
            console.log(typeof (chart.data.datasets[i].backgroundColor));
            text.push('<li>');
            var background_color = typeof (chart.data.datasets[i].backgroundColor) == 'object' ? chart.data.datasets[i].backgroundColor[0] : chart.data.datasets[i].backgroundColor;
            switch (chart.data.datasets[i].type) {
                case 'line':
                    text.push('<div class="line" style="background-color:' + background_color + '"></div>');
                    break;
                case 'bar':
                    text.push('<div class="bar" style="background-color:' + background_color + '"></div>');
                    break;
            }
            if (chart.data.datasets[i].label) {
                text.push(chart.data.datasets[i].label);
            }
            text.push('</li>');
        }
        text.push('</ul>');
        return text.join('');
    }
}