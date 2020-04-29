export class DateFormatter
{
    date: Date;
    
    constructor(date)
    {
        this.date = (function () { if (date == null) return new Date(); else return new Date(date); })();
    }
    format(fallback) {
        var dayIndex = this.date.getDate();
        var monthIndex = this.date.getMonth();
        var day = (dayIndex > 9)? this.date.getDate().toString() : "0" + dayIndex;
        var month = (monthIndex > 8)? (this.date.getMonth() + 1).toString() : "0" + (monthIndex + 1);
        var year = this.date.getFullYear();

        return fallback(day, month, year, dayIndex, monthIndex);
    }
    toString() {
        return this.date.toString();
    }
  
    static month(month) {
        
        const months = [
            "January", "February", "March",
            "April", "May", "June", "July",
            "August", "September", "October",
            "November", "December"
        ];
        
        return months[parseInt(month, 10)];
    }
    static monthShort(month) {
        
        const monthShorts = [
            "Jan", "Feb", "Mar", "Apr",
            "May", "Jun", "Jul", "Aug",
            "Sep", "Oct", "Nov", "Dec"
        ];
        return monthShorts[parseInt(month, 10)];
    }
}