$(document).ready(function () {
    let lastOrderId = null;
    // Request browser notification permission
    if ("Notification" in window) {
        Notification.requestPermission().then(permission => {
            if (permission !== "granted") {
                console.warn("Browser notifications are not enabled.");
            }
        });
    } else {
        console.warn("This browser does not support desktop notifications.");
    }

    $(document).on('change', '.order-status', function () {
        const orderId = $(this).data('order-id');
        const newStatus = $(this).val();
        console.log(orderId + newStatus);
    
        $.ajax({
            url: 'update_order_status.php',
            method: 'POST',
            data: {
                order_id: orderId,
                order_status: newStatus
            },
            success: function () {
                const myToastEl = document.getElementById('successToast');
                const toast = new bootstrap.Toast(myToastEl);
                toast.show();                
            },
            error: function () {
                const myToastEl = document.getElementById('failedToast');
                const toast = new bootstrap.Toast(myToastEl);
                toast.show(); 
            }
        });
    });
    

    function fetchOrders() {
        $.ajax({
            url: 'fetch_order.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if(data.error){
                    console.error(data.error);
                    return;
                }
    
                const tbody = document.querySelector('tbody');
                tbody.innerHTML = '';

                if(data.length === 0){
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="8" class="text-center">No Orders Found</td>';
                    tbody.appendChild(row);
                }

                data.forEach(order => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${order.id}</td>
                        <td>${order.name}</td>
                        <td>${order.email}</td>
                        <td>${order.products}</td>
                        <td>₱${parseFloat(order.total_price).toFixed(2)}</td>
                        <td>
                                <select class="form-select order-status" data-order-id="${order.id}">
                                    <option value="Pending" ${order.order_status === 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Ready To Pick-Up" ${order.order_status === 'Ready To Pick-Up' ? 'selected' : ''}>Ready To Pick-Up</option>
                                    <option value="Completed" ${order.order_status === 'Completed' ? 'selected' : ''}>Completed</option>
                                </select>
                        </td>
                        <td>${order.created_at}</td>
                        <td>
                        <form action="" method="post"> 
                        
                        </form>
                        <button type="button" 
                                    class="btn btn-danger delete-btn"
                                    data-id="${order.id}"
                                    data-status="${order.order_status}" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteConfirmationModal">
                                    Delete
                                </button>
                        </td>
                        `;
                        tbody.appendChild(row);
                });

            // Get the last row's ID
            const currentLatestOrderId = data[data.length - 1]?.id || null; 
            console.log("Current Latest Order ID (Last Row):", currentLatestOrderId);
            console.log("Last Known Order ID:", lastOrderId);

            // Check for a new order
            if (currentLatestOrderId && currentLatestOrderId !== lastOrderId) {
                console.log("New Order Detected!");
                showNewOrderNotification(data[data.length - 1]); // Notify for the last order
                lastOrderId = currentLatestOrderId; // Update the last known order ID
            }

            },
            error: function (xhr, status, error) {
                console.error('Error fetching orders:', error);
            }
        });  
    }

    function showNewOrderNotification(order) {
        if ("Notification" in window && Notification.permission === "granted") {
            const notification = new Notification("New Order Placed", {
                body: `Order #${order.id} by ${order.name} for ₱${parseFloat(order.total_price).toFixed(2)}`
            });
    
            notification.onclick = function () {
                window.focus();
                console.log(`Notification clicked for Order ID: ${order.id}`);
            };
        }
    }    

    $(document).on('click', '.delete-btn', function () {
        const orderId = $(this).data('id');
        const getStatus = $(this).data('status');
        console.log("Deleting Order ID: ", orderId);
        console.log("Order Status: ", getStatus);
        $('#deleteProductId').val(orderId);
        $('#getorder_status').val(getStatus);
    });
    

    setInterval(fetchOrders, 5000);
});
