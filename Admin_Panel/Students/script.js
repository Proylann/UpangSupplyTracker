document.addEventListener("DOMContentLoaded", function () {
    // Load departments first, then load students
    loadDepartments().then(() => {
        loadStudents();
    });

    // Modal functionality
    document.getElementById("addStudentBtn").addEventListener("click", function () {
        // Reset form to ensure it's clean for adding new student
        document.getElementById("studentForm").reset();
        document.getElementById("studentForm").removeAttribute("data-original-id");
        document.getElementById("modalTitle").textContent = "Add New Student";
        
        // Clear any previous error messages in the modal
        clearModalErrors();
        
        document.getElementById("studentModal").style.display = "block";
    });

    // Close modal with X button
    document.querySelector(".close-btn").addEventListener("click", function () {
        document.getElementById("studentModal").style.display = "none";
    });

    // Close modal with Cancel button
    document.getElementById("cancelBtn").addEventListener("click", function () {
        document.getElementById("studentModal").style.display = "none";
    });

    // Close modal when clicking outside
    window.onclick = function (event) {
        let modal = document.getElementById("studentModal");
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };

    // Add form submission handling
    document.getElementById("studentForm").addEventListener("submit", function(event) {
        event.preventDefault();
        saveStudent();
    });
});

// Add a function to clear modal errors
function clearModalErrors() {
    const modalErrorDiv = document.getElementById("modalErrorContainer");
    if (modalErrorDiv) {
        modalErrorDiv.innerHTML = "";
        modalErrorDiv.style.display = "none";
    }
}

// Add a function to show errors inside the modal
function showModalError(message) {
    let modalErrorDiv = document.getElementById("modalErrorContainer");

    if (!modalErrorDiv) {
        // If the error container doesn't exist, create it
        modalErrorDiv = document.createElement("div");
        modalErrorDiv.id = "modalErrorContainer";
        modalErrorDiv.className = "alert-danger";

        // Insert it at the top of the form
        const form = document.getElementById("studentForm");
        form.insertBefore(modalErrorDiv, form.firstChild);
    }

    // Set error message and display
    modalErrorDiv.textContent = message;
    modalErrorDiv.style.display = "block";
}


// References to UI elements
const departmentFilter = document.getElementById("filter-department");
const searchInput = document.getElementById("searchInput");
const studentsTableBody = document.getElementById("studentsTableBody");
const studentDepartmentSelect = document.getElementById("student-department");

// API URLs
const FETCH_STUDENTS_URL = "http://localhost/Backend/API/StudentApi/fetch_students.php";
const ADD_STUDENT_URL = "http://localhost/Backend/API/StudentApi/addStudent.php";
const UPDATE_STUDENT_URL = "http://localhost/Backend/API/StudentApi/editStudent.php";
const DELETE_STUDENT_URL = "http://localhost/Backend/API/StudentApi/deleteStudent.php";
const FETCH_DEPARTMENTS_URL = "http://localhost/backend/API/fetch_department.php";

let allStudents = [];
let departments = [];

// Fetch departments from the API
async function loadDepartments() {
    try {
        const response = await fetch(FETCH_DEPARTMENTS_URL);
        const data = await response.json();
        
        if (data.departments) {
            departments = data.departments;
            populateDepartmentDropdowns();
        }
    } catch (error) {
        console.error("Error fetching departments:", error);
        showAlert("Failed to load departments. Please try again later.", "error");
    }
}

// Populate department dropdowns with fetched data
function populateDepartmentDropdowns() {
    departmentFilter.innerHTML = '<option value="">All Departments</option>';
    studentDepartmentSelect.innerHTML = '<option value="">Select Department</option>';
    
    departments.forEach(dept => {
        const filterOption = document.createElement('option');
        filterOption.value = dept.Name;
        filterOption.textContent = dept.Name;
        departmentFilter.appendChild(filterOption);
        
        const formOption = document.createElement('option');
        formOption.value = dept.DepartmentID; 
        formOption.textContent = dept.Name;    
        studentDepartmentSelect.appendChild(formOption);
    });
}

// Fetch students from the API
function loadStudents() {
    fetch(FETCH_STUDENTS_URL)
        .then(response => response.json())
        .then(data => {
            if (data.students) {
                allStudents = data.students;
                updateStudentsTable(allStudents);
                
                // Setup edit and delete functionality after table is populated
                setupEditButtons();
                setupDeleteButtons();
            }
        })
        .catch(error => {
            console.error("Error fetching students:", error);
            showAlert("Failed to load students. Please try again later.", "error");
        });
}

// Function to update the table based on filters
function updateStudentsTable(students) {
    const filteredStudents = filterStudents(students);
    studentsTableBody.innerHTML = '';

    if (filteredStudents.length === 0) {
        studentsTableBody.innerHTML = `<tr><td colspan="4" style="text-align:center">No students found</td></tr>`;
        return;
    }

    filteredStudents.forEach(student => {
        let row = `<tr>
            <td>${student.StudentNumber}</td>
            <td>${student.FullName}</td>
            <td>${student.Name}</td>
            <td>
                <button class="action-btn edit-btn" data-id="${student.StudentNumber}">Edit</button>
                <button class="action-btn delete-btn" data-id="${student.StudentNumber}">Delete</button>
            </td>
        </tr>`;
        studentsTableBody.innerHTML += row;
    });
}

// Function to filter students based on search and department filter
function filterStudents(students) {
    const searchTerm = searchInput.value.toLowerCase();
    const departmentValue = departmentFilter.value.toLowerCase();

    return students.filter(student => {
        const matchesSearch = student.FullName.toLowerCase().includes(searchTerm) ||
                              student.StudentNumber.toLowerCase().includes(searchTerm);
        const matchesDepartment = !departmentValue || 
                                  student.Name.toLowerCase().includes(departmentValue);
        return matchesSearch && matchesDepartment;
    });
}

// Event Listeners for filtering
departmentFilter.addEventListener("change", () => updateStudentsTable(allStudents));
searchInput.addEventListener("input", () => updateStudentsTable(allStudents));

// Function to show alerts
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// Function to setup edit buttons
function setupEditButtons() {
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-id');
            const student = allStudents.find(s => s.StudentNumber === studentId);
            
            if (student) {
                // Clear any previous error messages
                clearModalErrors();
                
                // Populate the modal with student data
                document.getElementById('student-id').value = student.StudentNumber;
                document.getElementById('student-name').value = student.FullName;
                document.getElementById('student-department').value = student.DepartmentID;
                document.getElementById('student-password').value = ''; // Clear password field for security
                
                // Store original student number for reference
                document.getElementById('studentForm').setAttribute('data-original-id', student.StudentNumber);
                
                // Change form title and button text to indicate edit mode
                document.getElementById('modalTitle').textContent = 'Edit Student';
                
                // Display the modal
                document.getElementById('studentModal').style.display = 'block';
            }
        });
    });
}

// Function to setup delete buttons
function setupDeleteButtons() {
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-id');
            
            // Confirm before deleting
            if (confirm(`Are you sure you want to delete student with ID ${studentId}?`)) {
                deleteStudent(studentId);
            }
        });
    });
}

// Function to delete a student
function deleteStudent(studentNumber) {
    fetch(DELETE_STUDENT_URL, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ studentNumber: studentNumber })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.success);
            
            // Reload students to reflect changes
            loadStudents();
        } else if (data.error) {
            showAlert(data.error, "error");
        }
    })
    .catch(error => {
        console.error('Error deleting student:', error);
        showAlert("Failed to delete student. Please try again.", "error");
    });
}

// Function to save a new student or update existing student
function saveStudent() {
    const form = document.getElementById('studentForm');
    const originalId = form.getAttribute('data-original-id');
    const studentId = document.getElementById("student-id").value;
    const fullName = document.getElementById("student-name").value;
    const departmentId = document.getElementById("student-department").value;
    const password = document.getElementById("student-password").value;
    
    // Clear any previous errors
    clearModalErrors();
    
    // If originalId exists, it's an update operation
    if (originalId) {
        // Validation for update
        if (!studentId || !fullName || !departmentId) {
            showModalError("Please fill in all required fields");
            return;
        }
        
        // Prepare data for updating
        const studentData = {
            originalStudentNumber: originalId,
            StudentNumber: studentId,
            FullName: fullName,
            DepartmentID: parseInt(departmentId)
        };
        
        // Add password only if provided (optional for updates)
        if (password) {
            studentData.Password = password;
        }
        
        // Send update request
        fetch(UPDATE_STUDENT_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(studentData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.success);
                
                // Reset form and close modal
                form.reset();
                form.removeAttribute('data-original-id');
                document.getElementById('modalTitle').textContent = 'Add New Student';
                document.getElementById("studentModal").style.display = "none";
                
                // Reload students to reflect changes
                loadStudents();
            } else if (data.error) {
                // Show error inside the modal
                showModalError(data.error);
            }
        })
        .catch(error => {
            console.error('Error updating student:', error);
            showModalError("Failed to update student. Please try again.");
        });
    } else {
        // It's a new student operation
        
        // Validation for new student
        if (!studentId || !fullName || !departmentId || !password) {
            showModalError("Please fill in all required fields");
            return;
        }
        
        // Prepare data for adding new student
        const studentData = {
            StudentNumber: studentId,
            FullName: fullName,
            Password: password,
            DepartmentID: parseInt(departmentId)
        };
        
        // Send add request
        fetch(ADD_STUDENT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(studentData)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Failed to add student');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert(data.success);
                
                // Reset form and close modal
                form.reset();
                document.getElementById("studentModal").style.display = "none";
                
                // Reload students to reflect changes
                loadStudents();
            } else if (data.error) {
                // Show error inside the modal
                showModalError(data.error);
            }
        })
        .catch(error => {
            console.error('Error adding student:', error);
            // Show error inside the modal instead of outside
            showModalError(error.message || "Failed to add student. Please try again.");
        });
    }
}