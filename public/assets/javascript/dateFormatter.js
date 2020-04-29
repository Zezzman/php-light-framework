var DateFormatter = /** @class */ (function () {
    function DateFormatter(date) {
        this.date = (function () { if (date == null)
            return new Date();
        else
            return new Date(date); })();
    }
    DateFormatter.prototype.format = function (fallback) {
        var dayIndex = this.date.getDate();
        var monthIndex = this.date.getMonth();
        var day = (dayIndex > 9) ? this.date.getDate().toString() : "0" + dayIndex;
        var month = (monthIndex > 8) ? (this.date.getMonth() + 1).toString() : "0" + (monthIndex + 1);
        var year = this.date.getFullYear();
        return fallback(day, month, year, dayIndex, monthIndex);
    };
    DateFormatter.prototype.toString = function () {
        return this.date.toString();
    };
    DateFormatter.month = function (month) {
        var months = [
            "January", "February", "March",
            "April", "May", "June", "July",
            "August", "September", "October",
            "November", "December"
        ];
        return months[parseInt(month, 10)];
    };
    DateFormatter.monthShort = function (month) {
        var monthShorts = [
            "Jan", "Feb", "Mar", "Apr",
            "May", "Jun", "Jul", "Aug",
            "Sep", "Oct", "Nov", "Dec"
        ];
        return monthShorts[parseInt(month, 10)];
    };
    return DateFormatter;
}());
export { DateFormatter };
