// Event delegation for dynamically created .btn-primary buttons
$(document).on("click", "#inventoryContainer .btn-primary", function () {
  // Get product data from the clicked button
  const productID = $(this).attr("data-id");
  const productTitle = $(this).attr("data-name");
  const productImage = $(this).attr("data-image");
  const productPrice = $(this).attr("data-price");
  const productQty = $(this).attr("data-qty");
  const productSizes = JSON.parse($(this).attr("data-sizes") || "[]");

  // Populate the modal with product details
  $("#productID").text(productID);
  $("#modalTitle").text(productTitle);
  $("#modalImage").attr("src", productImage);
  $("#modalPrice").text(`Price: ₱${parseFloat(productPrice).toFixed(2)}`);
  $("#modalQty").text(`Stocks: ${productQty}`);

  // Populate the sizes dropdown
  const sizesDropdown = $("#sizes");
  sizesDropdown.empty(); // Clear existing options

  if (productSizes.length > 0) {
      productSizes.forEach((size) => {
          sizesDropdown.append(
              `<option value="${size.size}">${size.size}</option>`
          );
      });

      // Display stock for the first size by default
      $("#modalQty").text(`Stock: ${productSizes[0].stock}`);
  } else {
      sizesDropdown.append(`<option value="">No sizes available</option>`);
      $("#modalQty").text("Stock: N/A");
  }

  // Update stock display when size changes
  sizesDropdown.on("change", function () {
      const selectedSize = productSizes.find((size) => size.size === $(this).val());
      $("#modalQty").text(`Stock: ${selectedSize ? selectedSize.stock : "N/A"}`);
  });

  // Show the modal
  const modal = new bootstrap.Modal($("#productModal")[0]);
  modal.show();
});


// Fetch inventory from the server every 5 seconds
function fetchInventory() {
  $.ajax({
    url: "add_product.php?fetch_inventory=true", // Ensure this is the correct URL
    method: "GET",
    dataType: "json",
    success: function (data) {
      if (data.error) {
        console.error(data.error); // Handle errors if any
      } else {
        updateInventoryCards(data); // Update cards with new data
      }
    },
    error: function (xhr, status, error) {
      console.error("Error fetching inventory:", error);
      console.log("Response text:", xhr.responseText); // Log the raw response text
    },
  });
}


// Update inventory cards dynamically
function updateInventoryCards(data) {
  const container = document.querySelector("#inventoryContainer");
  container.innerHTML = ""; // Clear existing cards

  data.forEach((item) => {
    const sizes = JSON.stringify(item.sizes || []);
    const card = `
        <div class="col">
            <div class="card h-100">
                <img src="IMG/${item.product_image}" class="card-img-top" alt="${item.product_name}">
                <div class="card-body">
                    <h5 class="card-title">${item.product_name}</h5>
                    <p class="card-text fw-semibold">₱${parseFloat(item.price).toFixed(2)}</p>
                    <button type="button" 
                            class="btn btn-primary d-flex align-items-center justify-content-center"
                            data-id="${item.id}" 
                            data-name="${item.product_name}" 
                            data-image="IMG/${item.product_image}" 
                            data-price="${item.price}" 
                            data-qty="${item.qty}" 
                            data-sizes='${sizes}'>
                        <i class='bx bxs-cart-add fs-5 me-2'></i>
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>`;
    container.innerHTML += card;
  })
}

// Poll for inventory updates every 5 seconds
setInterval(fetchInventory, 5000);

// Toggle the cart section when the cart icon is clicked
const cartSection = document.getElementById("cart-section");
const cartIcon = document.getElementById("cart-icon");
const closeCart = document.getElementById("close-cart");

cartIcon.addEventListener("click", () => {
  cartSection.classList.toggle("open");
});

closeCart.addEventListener("click", () => {
  cartSection.classList.remove("open");
});

// Remove a specific product from the cart
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("bx-trash")) {
    const cartItem = e.target.closest(".cart-item");
    if (cartItem) {
      cartItem.remove();
    }
  }
});





const cart = []; 

function updateCartCounter() {
    const cartCounter = document.getElementById('cartCounter');
    const totalItems = cart.length;  
    cartCounter.textContent = totalItems; 
    syncCartWithServer();
}

function clearAll() {
  cart.length = 0;

  renderCart(cart);
  syncCartWithServer();
  updateCartCounter();

  console.log('All items in the cart are now deleted');
}

function addToCart() {
    const id = $("#productID").text();  
    const name = $("#modalTitle").text();  
    const price = parseFloat($("#modalPrice").text().replace("Price: ₱", "")); 
    const image = $("#modalImage").attr("src"); 
    const size = $("#sizes").val();  
    if (!size) {
        alert("Please select a size.");
        return;
    }

    console.log("Adding to cart: ", { id, name, price, image, size });

    
    const existingItemIndex = cart.findIndex(item => item.id === id && item.size === size);

    if (existingItemIndex !== -1) {
        
        cart[existingItemIndex].quantity += 1;
    } else {
        
        cart.push({ id, name, price, image, size, quantity: 1 });
    }

    
    renderCart(cart);
    syncCartWithServer();
    updateCartCounter();

    
    console.log('Cart after addition:', cart);
}

function renderCart(cartItems) {
    const cartContent = document.getElementById('cart-content');
    cartContent.innerHTML = ''; 

    
    if (!Array.isArray(cartItems) || cartItems.length === 0) {
        cartContent.innerHTML = '<p>Your cart is empty.</p>'; 
        return;
    }

    cartItems.forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.classList.add('cart-item', 'd-flex', 'align-items-center', 'justify-content-between');
        cartItem.innerHTML = `
            <img src="${item.image}" alt="${item.name}" class="h-25 w-25">
            <div>
                <p class="m-0">${item.name}</p>
                <p class="m-0">Size: <span>${item.size}</span></p>
                <p class="m-0">Qty: 
                    <i class='btn p-0 text-white bx bx-chevron-left' onclick="updateQuantity('${item.id}', '${item.size}', -1)"></i> 
                    <span class="text-center">${item.quantity}</span> 
                    <i class='btn p-0 text-white bx bx-chevron-right' onclick="updateQuantity('${item.id}', '${item.size}', 1)"></i>
                </p>
            </div>
            <i class='bx bx-trash' onclick="removeFromCart('${item.id}', '${item.size}')"></i>
        `;
        cartContent.appendChild(cartItem);
    });
}

function syncCartWithServer() {
    $.ajax({
        url: 'save_cart.php',
        type: 'POST',
        data: {
            cart: JSON.stringify(cart) 
        },
        success: function(response) {
            console.log('Cart synced with server:', response);
        },
        error: function(xhr, status, error) {
            console.error('Error syncing cart:', error);
        }
    });
}


function updateQuantity(id, size, delta) {
    const existingItemIndex = cart.findIndex(item => item.id === id && item.size === size);

    if (existingItemIndex !== -1) {
        cart[existingItemIndex].quantity += delta;

        if (cart[existingItemIndex].quantity <= 0) {
            removeFromCart(id, size);
        } else {
            renderCart(cart);
            syncCartWithServer();
        }
    }
}

function removeFromCart(id, size) {
    const existingItemIndex = cart.findIndex(item => item.id === id && item.size === size);

    if (existingItemIndex !== -1) {
        cart.splice(existingItemIndex, 1); 
        renderCart(cart);
        syncCartWithServer();
        updateCartCounter();
    }
}




window.onload = function() {
    $.ajax({
        url: 'fetch_cart.php',
        type: 'GET',
        success: function(response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                cart.length = 0; 
                cart.push(...data.cart); 
                renderCart(cart); 
            } else {
                console.error('Error:', data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching cart:', error);
        }
    });
};

function toCheckout(){
    location.replace("checkout.php");
}



