function validateForm() {
    let name = document.forms["patientForm"]["patient_name"].value;
    let age = document.forms["patientForm"]["patient_age"].value;

    if (name == "") {
        alert("Patient name is required!");
        return false;
    }

    if (age <= 0) {
        alert("Age must be greater than 0!");
        return false;
    }

    return true;
}

// Show doctor info when selected
document.getElementById("doctorSelect").addEventListener("change", function () {

    let selected = this.options[this.selectedIndex];
    let info = selected.getAttribute("data-info");

    document.getElementById("doctorInfo").innerHTML = info ? info : "";
});


// Basic validation (optional improvement)
function validateForm() {
    let doctor = document.getElementById("doctorSelect").value;

    if (doctor === "") {
        alert("Please select a doctor!");
        return false;
    }

    return true;
}

console.log("Dashboard loaded");

// Example: reset search
function resetSearch(){
    window.location.href = "?";
}