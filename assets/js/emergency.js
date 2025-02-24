// Autofill the date field with the current date
document.getElementById("date").value = new Date().toISOString().split('T')[0];

// Share current location using Geolocation API
function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function (position) {
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;
      document.getElementById("location").value = `https://www.google.com/maps?q=${latitude},${longitude}`;
    }, function () {
      alert("Unable to fetch location. Please enable location services.");
    });
  } else {
    alert("Geolocation is not supported by your browser.");
  }
}
