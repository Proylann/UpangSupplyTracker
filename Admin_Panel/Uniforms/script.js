// References to UI elements
const departmentFilter = document.getElementById("filter-department");
const searchInput = document.getElementById("searchInput");
const uniformTableBody = document.getElementById("uniformTableBody");
const uniformForm = document.getElementById("uniformForm");
const uniformModal = document.getElementById("uniformModal");
const modalTitle = document.querySelector(".modal-title");
const departmentSelect = document.getElementById("department");
const courseSelect = document.getElementById("course");
const uniformImageInput = document.getElementById("uniformImage");
const quantityInput = document.getElementById("quantity");
const uniformNameInput = document.getElementById("uniformName");
const uniformDescriptionInput = document.getElementById("uniformDescription");

// Base URL for API endpoints
const API_BASE_URL = "http://localhost/Backend/API/UniformsApi";

let allUniforms = []; // Store all uniforms globally
let isEditMode = false;
let currentUniformId = null;

// Modal event listeners
document.getElementById("addUniformBtn").addEventListener("click", function () {
    openAddModal();
});

document.getElementById("closeUniformModal").addEventListener("click", function () {
    closeModal();
});

document.getElementById("cancelUniformBtn").addEventListener("click", function () {
    closeModal();
});

// Close modal when clicking outside
window.onclick = function (event) {
    if (event.target === uniformModal) {
        closeModal();
    }
};

// Load everything when page loads
document.addEventListener("DOMContentLoaded", function () {
    loadUniforms();
    loadDepartments();
});

// Handle department change to load related courses
departmentSelect.addEventListener("change", function () {
    const departmentId = this.value;
    if (departmentId) {
        loadCourses(departmentId);
    } else {
        courseSelect.innerHTML = '<option value="">Select Course</option>';
    }
});

// Fetch uniforms from the API
function loadUniforms() {
    fetch(`${API_BASE_URL}/fetch_uniform.php`)
        .then(response => response.json())
        .then(data => {
            allUniforms = data.uniforms;
            updateUniformTable(allUniforms);
        })
        .catch(error => console.error("Error fetching Uniforms:", error));
}

// Load departments for the dropdown
async function loadDepartments() {
    try {
        const response = await fetch("http://localhost/Backend/API/fetch_department.php");
        const data = await response.json();

        if (data.departments) {
            departmentSelect.innerHTML = `<option value="">Select Department</option>`;
            data.departments.forEach((dept) => {
                let option = document.createElement("option");
                option.value = dept.DepartmentID;
                option.textContent = dept.Name;
                departmentSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error("Error fetching departments:", error);
    }
}

// Fetch courses based on the selected department
function loadCourses(departmentID) {
    return fetch(`http://localhost/Backend/API/fetch_course.php?departmentID=${departmentID}`)
        .then(response => response.json())
        .then(data => {
            courseSelect.innerHTML = `<option value="">Select Course</option>`;
            if (data.success && data.courses.length > 0) {
                data.courses.forEach(course => {
                    let option = document.createElement("option");
                    option.value = course.CourseID;
                    option.textContent = course.CourseName;
                    courseSelect.appendChild(option);
                });
            } else {
                courseSelect.innerHTML = `<option value="">No courses available</option>`;
            }
        })
        .catch(error => console.error("Error fetching courses:", error));
}


// Function to update the table based on filters
function updateUniformTable(uniforms) {
    uniformTableBody.innerHTML = "";
    if (uniforms.length === 0) {
        uniformTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center">No uniforms found</td></tr>`;
        return;
    }

    uniforms.forEach((uniform) => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${uniform.ID}</td>
            <td>${uniform.Preview ? `<img src="data:image/jpeg;base64,${uniform.Preview}" width="50" height="50">` : "No Image"}</td>
            <td>${uniform.Name}</td>
            <td>${uniform.Department}</td>
            <td>${uniform.Course || "N/A"}</td>
            <td>${uniform.Stock}</td>
            <td>
                <button class="action-btn edit-btn" data-id="${uniform.ID}">Edit</button>
                <button class="action-btn delete-btn" data-id="${uniform.ID}">Delete</button>
            </td>
        `;

        uniformTableBody.appendChild(row);

        row.querySelector(".edit-btn").addEventListener("click", function () {
            openEditModal(uniform.ID);
        });

        row.querySelector(".delete-btn").addEventListener("click", function () {
            if (confirm("Are you sure you want to delete this uniform?")) {
                deleteUniform(uniform.ID);
            }
        });
    });
}

// Function to open the add modal
function openAddModal() {
    uniformForm.reset();
    modalTitle.textContent = "Add New Uniform";
    isEditMode = false;
    currentUniformId = null;
    
    // Enable all fields for adding a new uniform
    uniformNameInput.disabled = false;
    departmentSelect.disabled = false;
    courseSelect.disabled = false;
    uniformDescriptionInput.disabled = false;
    
    uniformModal.style.display = "block";
}

// Function to open the edit modal
function openEditModal(uniformId) {
    const uniform = allUniforms.find((u) => u.ID == uniformId);
    if (!uniform) {
        alert("Uniform not found");
        return;
    }

    // Set all fields but make non-editable ones disabled
    uniformNameInput.value = uniform.Name || "";
    uniformNameInput.disabled = true;
    
    departmentSelect.value = uniform.DepartmentID || "";
    departmentSelect.disabled = true;

    // Load courses dynamically before setting course value
    loadCourses(uniform.DepartmentID).then(() => {
        courseSelect.value = uniform.CourseID || "";
        courseSelect.disabled = true;
    });

    uniformDescriptionInput.value = uniform.Description || "";
    uniformDescriptionInput.disabled = true;
    
    // Only enable stock quantity for editing
    quantityInput.value = uniform.Stock || 0;
    quantityInput.disabled = false;
    
    // Image is always editable
    uniformImageInput.disabled = false;

    modalTitle.textContent = "Edit Uniform";
    isEditMode = true;
    currentUniformId = uniformId;

    uniformModal.style.display = "block";
}

// Close modal function
function closeModal() {
    uniformModal.style.display = "none";
}

// Handle form submission for add/edit
uniformForm.addEventListener("submit", function (event) {
    event.preventDefault();
    const formData = new FormData(this);

    if (isEditMode && currentUniformId) {
        // For edit mode, create a new FormData with only the allowed fields
        const editFormData = new FormData();
        
        // Add only the ID, image, and quantity to the form data
        editFormData.append("uniform_id", currentUniformId);
        
        // Add image if provided
        if (uniformImageInput.files[0]) {
            editFormData.append("uniformImage", uniformImageInput.files[0]);
        }
        
        // Add quantity
        editFormData.append("quantity", quantityInput.value);
        
        // Add the original values for other fields that shouldn't be changed
        const uniform = allUniforms.find((u) => u.ID == currentUniformId);
        editFormData.append("uniformName", uniform.Name);
        editFormData.append("department", uniform.DepartmentID);
        editFormData.append("course", uniform.CourseID || "");
        editFormData.append("uniformDescription", uniform.Description || "");

        fetch(`${API_BASE_URL}/editUniform.php`, {
            method: "POST",
            body: editFormData,
        })
        .then(response => response.json())
        .then((data) => {
            if (data.success) {
                alert("Uniform updated successfully!");
                closeModal();
                loadUniforms();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch((error) => console.error("Error updating uniform:", error));

    } else {
        // Adding a new uniform - use all form data
        fetch(`${API_BASE_URL}/addUniform.php`, {
            method: "POST",
            body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                alert("Uniform added successfully!");
                closeModal();
                loadUniforms();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch((error) => console.error("Error adding uniform:", error));
    }
});

// Function to delete a uniform
function deleteUniform(uniformId) {
    console.log("Deleting uniform ID:", uniformId); // Debugging line

    fetch(`${API_BASE_URL}/deleteUniform.php`, {
        method: "POST",
        body: JSON.stringify({ id: uniformId }),
        headers: { "Content-Type": "application/json" }
    })
    .then(response => response.json())
    .then(data => {
        console.log("Delete response:", data); // Debugging response
        if (data.success) {
            alert("Uniform deleted successfully");
            loadUniforms();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => console.error("Error deleting uniform:", error));
}

// Event Listeners for filtering
departmentFilter.addEventListener("change", () => updateUniformTable(allUniforms));
searchInput.addEventListener("input", () => updateUniformTable(allUniforms));