document.addEventListener("DOMContentLoaded", () => {
  // Handle the Edit button click to populate modal fields
  const editButtons = document.querySelectorAll('[data-bs-target="#editProduct"]');

  editButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const id = button.getAttribute("data-id");
      const name = button.getAttribute("data-name");
      const gender = button.getAttribute("data-gender");
      const price = button.getAttribute("data-price");
      const qty = button.getAttribute("data-qty");

      console.log("Product Quantity: ", qty);

      // Populate the form fields in the modal
      document.getElementById("editName").value = name;
      document.getElementById("editPrice").value = price;
      document.getElementById("editQty").value = `Total Quantity: ${qty}`;  // Populate with existing qty

      // Set gender radio button
      document.getElementById("editGenderFemale").checked = gender === "Female";
      document.getElementById("editGenderMale").checked = gender === "Male";
      document.getElementById("editGenderNone").checked = gender === "None";

      // Add the product ID to the form as a hidden field
      let idField = document.querySelector("input[name='id']");
      if (!idField) {
        idField = document.createElement("input");
        idField.type = "hidden";
        idField.name = "id";
        document.querySelector("#editProduct form").appendChild(idField);
      }
      idField.value = id;

      // Clear any existing size-stock input fields
      const sizeStockInputs = document.getElementById('editSizeStockInputs');
      sizeStockInputs.innerHTML = ''; // Clear previous size-stock fields

      // Fetch the current sizes and stocks for the product
      fetch(`get_product_sizes.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
          data.forEach((sizeStock) => {
            // Populate the modal with the current sizes and stocks
            const sizeStockDiv = document.createElement('div');
            sizeStockDiv.classList.add('input-group', 'mb-2');
            sizeStockDiv.innerHTML = `
                <input type="text" name="sizes[]" class="form-control" value="${sizeStock.size}" required>
                <input type="number" name="stocks[]" class="form-control" value="${sizeStock.stock}" min="0" required>
                <button type="button" class="btn btn-danger remove-size-btn">Remove</button>
            `;
            sizeStockInputs.appendChild(sizeStockDiv);
          });
        })
        .catch(error => console.error('Error fetching sizes:', error));
    });
  });

  document.getElementById('addSizeStockBtn').addEventListener('click', function() {
    const sizeStockInputs = document.getElementById('sizeStockInputs');

    const newSizeStock = document.createElement('div');
    newSizeStock.classList.add('input-group', 'mb-2');

    newSizeStock.innerHTML = `
        <input type="text" name="sizes[]" class="form-control" placeholder="Size (e.g., S)" required>
        <input type="number" name="stocks[]" class="form-control" placeholder="Stock for this size" min="0" required>
        <button type="button" class="btn btn-danger remove-size-btn">Remove</button>
    `;
    
    sizeStockInputs.appendChild(newSizeStock);
  });

  // Remove size-stock input pair
  document.getElementById('sizeStockInputs').addEventListener('click', function(event) {
    if (event.target.classList.contains('remove-size-btn')) {
      event.target.parentElement.remove();
    }
  });
  
  // Add size and stock inputs dynamically
  document.getElementById('addEditSizeStockBtn').addEventListener('click', function() {
    const sizeStockInputs = document.getElementById('editSizeStockInputs');
    
    const newSizeStock = document.createElement('div');
    newSizeStock.classList.add('input-group', 'mb-2');
    
    newSizeStock.innerHTML = `
        <input type="text" name="sizes[]" class="form-control" placeholder="Size (e.g., S)" required>
        <input type="number" name="stocks[]" class="form-control" placeholder="Stock for this size" min="0" required>
        <button type="button" class="btn btn-danger remove-size-btn">Remove</button>
    `;
    
    sizeStockInputs.appendChild(newSizeStock);
  });

  // Remove size-stock input pair
  document.getElementById('editSizeStockInputs').addEventListener('click', function(event) {
    if (event.target.classList.contains('remove-size-btn')) {
        event.target.parentElement.remove();
    }
  });

  // Handle the Delete button click to populate modal with the product ID
  const deleteButtons = document.querySelectorAll('.delete-btn');

  deleteButtons.forEach((button) => {
    button.addEventListener('click', function () {
      const productId = button.getAttribute('data-id'); // Get the product ID
      console.log("Deleting Product ID: ", productId);
      document.getElementById('deleteProductId').value = productId; // Set it in the hidden input
    });
  });
  
});
