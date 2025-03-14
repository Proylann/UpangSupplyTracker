document.addEventListener('DOMContentLoaded', function() {
    const moduleForm = document.getElementById('moduleForm');
    const modulesTable = document.getElementById('modulesTable').getElementsByTagName('tbody')[0];
    const searchInput = document.querySelector('.search-box input');
    const departmentFilter = document.getElementById('filter-department');
    const yearFilter = document.getElementById('filter-year');

    let modules = [];

    // Add new module
    moduleForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newModule = {
            title: document.getElementById('module-title').value,
            code: document.getElementById('module-code').value,
            subject: document.getElementById('module-subject').value,
            department: document.getElementById('module-department').options[document.getElementById('module-department').selectedIndex].text,
            yearLevel: document.getElementById('module-year').options[document.getElementById('module-year').selectedIndex].text,
            quantity: parseInt(document.getElementById('module-quantity').value),
            status: getStatus(parseInt(document.getElementById('module-quantity').value))
        };

        modules.push(newModule);
        updateTable();
        moduleForm.reset();
    });

    // Filter modules
    function filterModules() {
        const searchTerm = searchInput.value.toLowerCase();
        const departmentValue = departmentFilter.value;
        const yearValue = yearFilter.value;

        return modules.filter(module => {
            const matchesSearch = module.title.toLowerCase().includes(searchTerm) ||
                                module.code.toLowerCase().includes(searchTerm) ||
                                module.subject.toLowerCase().includes(searchTerm);
            
            const matchesDepartment = !departmentValue || 
                                    module.department.toLowerCase().includes(departmentValue);
            
            const matchesYear = !yearValue || 
                              module.yearLevel.toLowerCase().includes(`year ${yearValue}`);

            return matchesSearch && matchesDepartment && matchesYear;
        });
    }

    // Update table
    function updateTable() {
        const filteredModules = filterModules();
        modulesTable.innerHTML = '';

        filteredModules.forEach((module, index) => {
            const row = modulesTable.insertRow();
            row.innerHTML = `
                <td>${module.title}</td>
                <td>${module.code}</td>
                <td>${module.subject}</td>
                <td>${module.department}</td>
                <td>${module.yearLevel}</td>
                <td>${module.quantity}</td>
                <td><span class="status ${module.status.toLowerCase().replace(' ', '-')}">${module.status}</span></td>
                <td>
                    <button class="action-btn" onclick="editModule(${index})">Edit</button>
                    <button class="action-btn delete-btn" onclick="deleteModule(${index})">Delete</button>
                </td>
            `;
        });
    }

    // Get status based on quantity
    function getStatus(quantity) {
        if (quantity <= 0) return 'Out of Stock';
        if (quantity <= 5) return 'Low Stock';
        return 'Available';
    }

    // Event listeners for search and filters
    searchInput.addEventListener('input', updateTable);
    departmentFilter.addEventListener('change', updateTable);
    yearFilter.addEventListener('change', updateTable);

    // Initial table update
    updateTable();
});

// Edit and Delete functions
function editModule(index) {
    // Implement edit functionality
    alert('Edit functionality to be implemented');
}

function deleteModule(index) {
    if (confirm('Are you sure you want to delete this module?')) {
        modules.splice(index, 1);
        updateTable();
    }
}