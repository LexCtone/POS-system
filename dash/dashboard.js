function updateClock() {
    const now = new Date();
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const hoursString = String(hours).padStart(2, '0');
    
    const timeString = `${hoursString}:${minutes}:${seconds} ${ampm}`;
    
    document.getElementById('clock').textContent = timeString;
}

function updateDate() {
    const now = new Date();
    const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const monthsOfYear = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const dayName = daysOfWeek[now.getDay()];
    const day = String(now.getDate()).padStart(2, '0');
    const month = monthsOfYear[now.getMonth()];
    const year = now.getFullYear();

    const dateString = `${dayName}, ${month} ${day}, ${year}`;
    
    document.getElementById('date').textContent = dateString;
}

// Update the clock and date immediately
updateClock();
updateDate();
// Update the clock every second
setInterval(updateClock, 1000);
// Update the date every day (once per day)
setInterval(updateDate, 86400000); // 86400000 milliseconds = 1 day
