document.addEventListener("DOMContentLoaded", function () {
    fetchInventory();
    
    // Add event listener for search clear button
    document.getElementById("clear-search").addEventListener("click", function() {
        document.getElementById("search-bar").value = "";
        filterTable();
    });

    // Add event listeners for sorting
    document.querySelectorAll("thead th").forEach((header, index) => {
        if (index !== 0) { // Skip the Preview column
            header.addEventListener("click", function() {
                sortTable(index);
            });
            header.classList.add("sortable");
        }
    });

    document.getElementById("category-filter").addEventListener("change", filterTable);
    document.getElementById("search-bar").addEventListener("input", filterTable);
});

// Global variables for sorting
let currentSortColumn = -1;
let currentSortDirection = 1;
let tableData = [];

function fetchInventory() {
    fetch("http://localhost/Backend/API/DashboardApi/fetch_items.php")
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            console.log("Data received:", data);
            tableData = data.items;
            
            document.getElementById("total-items").textContent = data.stats.totalItems;
            document.getElementById("low-stock").textContent = data.stats.lowStockItems;
            
            renderTable(tableData);
        })
        .catch(error => {
            console.error("Error fetching data:", error);
            document.getElementById("inventory-table").innerHTML = 
                `<tr><td colspan="6">Error loading inventory data: ${error.message}. Please try again later.</td></tr>`;
        });
}
function renderTable(items) {
    const tableBody = document.getElementById("inventory-table");
    tableBody.innerHTML = "";

    items.forEach(item => {
        let statusClass = "";
        let statusText = "";

        if (item.Quantity == 0) {
            statusClass = "no-stock";
            statusText = "No Stock";
        } else if (item.Quantity < 100) {
            statusClass = "low-stock";
            statusText = "Low";
        } else if (item.Quantity > 300) {
            statusClass = "high-stock";
            statusText = "High";
        } else {
            statusClass = "normal-stock";
            statusText = "Normal";
        }

        let previewHtml = "<span class='no-preview'>No preview</span>";
        if (item.preview) {
            let mimeType = item.type === "book" || item.type === "module" ? "application/pdf" : "image/jpeg";
            previewHtml = mimeType.startsWith("image/") ?
                `<img src="data:${mimeType};base64,${item.preview}" alt="${item.name}" class="preview-img">` :
                `<a href="data:${mimeType};base64,${item.preview}" target="_blank"><i class="fas fa-file-alt preview-icon"></i></a>`;
        }

        const row = document.createElement("tr");
        row.innerHTML = `
            <td><img src="data:image/png;base64,${item.preview}" alt="Preview" width="50"></td>
            <td>${item.name || 'N/A'}</td>
            <td>${item.department || 'N/A'}</td>
            <td>${item.Course || 'N/A'}</td>
            <td data-value="${item.Quantity}">${item.Quantity}</td>
            <td><span class="status ${statusClass}">${statusText}</span></td>
        `;
        tableBody.appendChild(row);
    });
}

function filterTable() {
    let category = document.getElementById("category-filter").value.toLowerCase();
    let searchQuery = document.getElementById("search-bar").value.toLowerCase();

    let filteredData = tableData.filter(item => {
        let itemCategory = item.type.toLowerCase(); // Use `type` instead of `department`
        let itemName = item.name ? item.name.toLowerCase() : "";

        let categoryMatch = category === "all" || itemCategory === category;
        let searchMatch = searchQuery === "" || itemName.includes(searchQuery);

        return categoryMatch && searchMatch;
    });

    renderTable(filteredData);
}

function sortTable(columnIndex) {
    if (currentSortColumn === columnIndex) {
        currentSortDirection *= -1;
    } else {
        currentSortDirection = 1;
        currentSortColumn = columnIndex;
    }

    document.querySelectorAll("thead th").forEach(header => {
        header.classList.remove("sort-asc", "sort-desc");
    });

    const headers = document.querySelectorAll("thead th");
    headers[columnIndex].classList.add(currentSortDirection === 1 ? "sort-asc" : "sort-desc");

    tableData.sort((a, b) => {
        let valueA, valueB;
        switch(columnIndex) {
            case 1: valueA = a.name || ''; valueB = b.name || ''; break;
            case 2: valueA = a.department || ''; valueB = b.department || ''; break;
            case 3: valueA = a.Course || ''; valueB = b.Course || ''; break;
            case 4: valueA = parseInt(a.Quantity); valueB = parseInt(b.Quantity); break;
            case 5: valueA = parseInt(a.Quantity); valueB = parseInt(b.Quantity); break;
            default: valueA = ''; valueB = '';
        }
        return typeof valueA === 'number' && typeof valueB === 'number' ?
            currentSortDirection * (valueA - valueB) :
            currentSortDirection * String(valueA).localeCompare(String(valueB));
    });
    renderTable(tableData);
    filterTable();
}
