document.getElementById('addBook').addEventListener('click', function () {
    document.getElementById('booksModal').style.display = 'block';
    loadDepartments();
});

document.getElementById('closeBookModal').addEventListener('click', function () {
    document.getElementById('booksModal').style.display = 'none';
});

document.getElementById('cancelBookBtn').addEventListener('click', function () {
    document.getElementById('booksModal').style.display = 'none';
});

window.onclick = function (event) {
    let modal = document.getElementById('booksModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

document.addEventListener("DOMContentLoaded", function () {
    loadBooks();
});

// Fetch books from the API
function loadBooks() {
    fetch("http://localhost/Backend/API/BooksApi/fetch_books.php")
        .then(response => response.json())
        .then(data => {
            const booksTable = document.getElementById("booksTableBody");
            booksTable.innerHTML = "";

            data.books.forEach(book => {
                const row = document.createElement("tr");

                row.innerHTML = `
                    <td>${book.ID}</td>
                    <td>
                        ${book.Preview ? `<img src="data:image/jpeg;base64,${book.Preview}" width="50">` : "No Image"}
                    </td>
                    <td>${book.BookTitle}</td>
                    <td>${book.Department}</td>
                    <td>${book.Course}</td>
                    <td>${book.Quantity}</td>
                    <td>
                        <button class="action-btn edit-btn" data-id="${book.ID}">Edit</button>
                        <button class="action-btn delete-btn" data-id="${book.ID}">Delete</button>
                    </td>
                `;

                booksTable.appendChild(row);

                   // Attach event listeners to delete buttons after they're added to the DOM
            const deleteButtons = row.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', deleteBook);
            });

            const editButtons = row.querySelectorAll('.edit-btn');
                editButtons.forEach(button => {
                    button.addEventListener('click', function(event) {
                        // Extract book data from the row or fetch it again
                        const bookID = event.target.dataset.id; // Or event.target.closest('tr').dataset.bookId;
                        openEditModal(bookID);
                    });
                });
            });



        })
        .catch(error => console.error("Error fetching books:", error));
}

// Prevent form submission and handle it via JavaScript
document.getElementById('bookForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent the form from submitting normally
    document.getElementById('saveBookBtn').click(); // Trigger the save button click
});

document.getElementById('saveBookBtn').addEventListener('click', function (event) {
    event.preventDefault(); // Prevent any default button behavior
    
    // Use the correct ID from the HTML: bookName instead of bookTitle
    const bookTitle = document.getElementById("bookName").value.trim();
    const departmentID = document.getElementById("department").value;
    const course = document.getElementById("course").value;
    const quantity = document.getElementById("quantity").value.trim();

    // Add debugging to see what's being captured
    console.log("Book Title:", bookTitle);
    console.log("Department:", departmentID);
    console.log("Course:", course);
    console.log("Quantity:", quantity);

    if (!bookTitle || !departmentID || !course || !quantity) {
        alert("Please fill in all required fields.");
        return;
    }

    // Prepare data for API request
    const bookData = {
        BookTitle: bookTitle,
        Department: departmentID,
        Course: course,
        Quantity: quantity
    };

    // Log the data being sent to the server
    console.log("Sending data:", bookData);

    fetch("http://localhost/Backend/API/BooksApi/addBooks.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(bookData)
    })
    .then(response => {
        console.log("Response status:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("Response data:", data);
        if (data.success) {
            alert("Book added successfully!");
            document.getElementById('booksModal').style.display = 'none'; // Close modal
            document.getElementById('bookForm').reset(); // Reset the form
            loadBooks(); // Refresh the books list
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => {
        console.error("Error adding book:", error);
        alert("An error occurred while adding the book. Check console for details.");
    });
});

// Delete book function
function deleteBook(event) {
    const bookID = event.target.dataset.id; // Get book ID from data-id attribute

    if (confirm("Are you sure you want to delete this book?")) {
        fetch("http://localhost/Backend/API/BooksApi/deleteBooks.php", { // Ensure correct URL for delete
            method: "POST", // Or "DELETE" if your API strictly requires it
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ bookID: bookID }) // Send the book ID in the request body
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert("Book deleted successfully!");
                loadBooks(); // Refresh the books list
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => {
            console.error("Error deleting book:", error);
            alert("An error occurred while deleting the book. Check console for details.");
        });
    }
}

// Edit book Function
function openEditModal(bookID) {
    // Fetch book details based on bookID
    fetch(`http://localhost/Backend/API/BooksApi/getBookDetails.php?bookID=${bookID}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Populate the edit modal with book data
                document.getElementById('editBookID').value = data.book.ID;
                document.getElementById('editBookName').value = data.book.BookTitle;
                document.getElementById('editDepartment').value = data.book.DepartmentID; // Assuming you want to keep the ID
                document.getElementById('editCourse').value = data.book.CourseID;       // Assuming you want to keep the ID
                document.getElementById('editQuantity').value = data.book.Quantity;

                // Show the edit modal
                document.getElementById('editBookModal').style.display = 'block';
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => {
            console.error("Error fetching book details:", error);
            alert("An error occurred while fetching book details. Check console for details.");
        });
}

// Close the edit modal
document.getElementById('closeEditBookModal').addEventListener('click', function () {
    document.getElementById('editBookModal').style.display = 'none';
});

// Cancel edit button action
document.getElementById('cancelEditBookBtn').addEventListener('click', function () {
    document.getElementById('editBookModal').style.display = 'none';
});

// Inside your script.js
document.getElementById('updateBookBtn').addEventListener('click', function (event) {
    event.preventDefault();

    const bookID = document.getElementById('editBookID').value;
    const bookTitle = document.getElementById('editBookName').value;
    const departmentID = document.getElementById('editDepartment').value;
    const courseID = document.getElementById('editCourse').value;
    const quantity = document.getElementById('editQuantity').value;

    // Validate fields
    if (!bookTitle || !departmentID || !courseID || !quantity) {
        alert("Please fill in all required fields.");
        return;
    }

    const bookData = {
        ID: bookID,
        BookTitle: bookTitle,
        Department: departmentID,
        Course: courseID,
        Quantity: quantity
    };

    fetch("http://localhost/Backend/API/BooksApi/updateBooks.php", { // Corrected URL
        method: "POST", // Or PUT, depending on your API
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(bookData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert("Book updated successfully!");
            document.getElementById('editBookModal').style.display = 'none';
            loadBooks(); // Refresh the book list
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => {
        console.error("Error updating book:", error);
        alert("An error occurred while updating the book. Check console for details.");
    });
});


// Fetch departments and populate the department dropdown in the book modal
async function loadDepartments() {
    try {
        const response = await fetch("http://localhost/Backend/API/fetch_department.php");
        const data = await response.json();

        if (data.departments) {
            const departmentSelect = document.getElementById("department");
            departmentSelect.innerHTML = `<option value="">Select Department</option>`;

            data.departments.forEach(dept => {
                let option = document.createElement("option");
                option.value = dept.DepartmentID;
                option.textContent = dept.Name;
                departmentSelect.appendChild(option);
            });

            // Set up event listener for department selection
            departmentSelect.addEventListener("change", function () {
                const departmentID = this.value;
                if (departmentID) {
                    loadCourses(departmentID);
                } else {
                    clearCourses();
                }
            });
        }
    } catch (error) {
        console.error("Error fetching departments:", error);
        alert("Failed to load departments. Please try again.");
    }
}

// Fetch courses based on the selected department
function loadCourses(departmentID) {
    fetch(`http://localhost/Backend/API/fetch_course.php?departmentID=${departmentID}`)
        .then(response => response.json())
        .then(data => {
            const courseSelect = document.getElementById("course");
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

// Clear courses dropdown when no department is selected
function clearCourses() {
    const courseSelect = document.getElementById("course");
    courseSelect.innerHTML = `<option value="">Select Course</option>`;
}
