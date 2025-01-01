$(document).ready(function () {
    console.log($('#monthSelector'));

    const getValue = $('#monthSelector').val();
    console.log("Value:" + getValue);

    const [year, month] = getValue.split('-')
    console.log("Year: " + year + "Month: " + month);

    $('#salesContainer').empty();

    function fetchSales() {
        $.ajax({
            url: 'fetch_sales.php',  // Path to your PHP script
            type: 'POST',
            data: {
                year: year,
                month: month
            },
            dataType: 'json',
            success: function (response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    renderSalesData(response);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('AJAX Error: ' + textStatus + ': ' + errorThrown);
            }
        });
    }

        // function renderSalesData(response){
        //     if(response.sales && response.sales.length > 0){
        //         var displaySales = '';

        //         response.sales.forEach(function (sale, index) {
        //             let monthName = new Date(sale.created_at).toLocaleString('default', { month: 'long' }); // Get month name
        //             let year = new Date(sale.created_at).getFullYear(); // Get year

        //             displaySales += `
        //             <div class="accordion">
        //                 <div class="accordion-item">
        //                     <h2 class="accordion-header"  id="heading${index}">
        //                         <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
        //                     </h2>
        //                 </div>
        //             </div>
        //                 `;
        //         });

        //         $('#salesContainer').html(displaySales);
        //     }
        // }

        function renderSalesData(response) {
            const openAccordion = $('.accordion-collapse.show').attr('id');
            if (response.sales && response.sales.length > 0) {
                const processedMonths = new Set(); // Track months already processed
                let html = ''; // HTML to be rendered
        
                response.sales.forEach(function (sale) {
                    const saleDate = new Date(sale.completed_at);
                    const year = saleDate.getFullYear();
                    const month = saleDate.getMonth() + 1; // JS months are 0-indexed
                    const monthName = saleDate.toLocaleString('default', { month: 'long' });
                    const monthKey = `${year}-${month}`; // Create a unique key for each month
        
                    if (!processedMonths.has(monthKey)) {
                        processedMonths.add(monthKey); // Mark this month as processed
        
                        // Filter sales for the current month
                        const monthSales = response.sales.filter(s => {
                            const date = new Date(s.completed_at);
                            return date.getFullYear() === year && date.getMonth() + 1 === month;
                        });
        
                        // Calculate total amount for this month
                        const totalAmount = monthSales.reduce((sum, s) => sum + parseFloat(s.total_price), 0);
        
                        // Create accordion item
                        html += `
                            <div class="accordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading${monthKey}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${monthKey}" aria-expanded="false" aria-controls="collapse${monthKey}">
                                            ${monthName} ${year}
                                        </button>
                                    </h2>
                                    <div id="collapse${monthKey}" class="accordion-collapse collapse" aria-labelledby="heading${monthKey}" data-bs-parent="#salesAccordion">
                                        <div class="accordion-body">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Products</th>
                                                        <th>Total Price</th>
                                                        <th>Order Status</th>
                                                        <th>Completed At</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;
        
                        // Add rows for each sale in this month
                        monthSales.forEach(function (sale) {
                            html += `<tr>
                                        <td>${sale.id}</td>
                                        <td>${sale.name}</td>
                                        <td>${sale.email}</td>
                                        <td>${sale.products.replace(/<br>/g, "<br />")}</td>
                                        <td>${sale.total_price}</td>
                                        <td>${sale.order_status}</td>
                                        <td>${sale.completed_at}</td>
                                    </tr>`;
                        });
        
                        html += `</tbody>
                                </table>
                                <p>Total Amount for this Month: ${totalAmount.toFixed(2)}</p>
                                <caption>Total Purchased Items</caption>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Size</th>
                                            <th>Total Number of Purchases</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
        
                        // Add rows for product summary
                        response.summary.forEach(function (summary) {
                            html += `<tr>
                                        <td>${summary.name}</td>
                                        <td>${summary.size}</td>
                                        <td>${summary.total}</td>
                                     </tr>`;
                        });
        
                        html += `</tbody>
                                </table>
                                <form method="post" action="download.php">
                                    <input type="hidden" name="year" value="${year}">
                                    <input type="hidden" name="month" value="${month}">
                                    <button type="submit" class="btn btn-primary">Download ${monthName} ${year} Data</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>`;
                    }
                });
        
                // Update the sales container with the new HTML
                $('#salesContainer').html(html);
            }
            if (openAccordion) {
                $(`#${openAccordion}`).addClass('show');
            }
        }
        
    setInterval(fetchSales, 5000)
});

// $(document).ready(function () {

//     console.log($('#monthSelector'));

//     if (typeof $ !== 'undefined') {
//         console.log("jQuery is loaded");
//     } else {
//         console.log("jQuery is not loaded");
//     }
    

//     if ($('#monthSelector').length > 0) {
//         console.log("Month Selector exists");
//     } else {
//         console.log("Month Selector not found");
//     }

//     $(document).on('change', '#monthSelector', function() {
//         const getValue = $(this).value;
//         console.log("Value:");
//     })
    

//     // On month selection change
//     // $('#monthSelector').change(function () {
//     //     // Get the selected month value in the format "YYYY-MM"
//     //     var selectedMonth = $(this).val();  
        
//     //     // Split the selected month into year and month
//     //     const [year, month] = selectedMonth.split('-');
        
//     //     // Log the year and month for debugging
        

//     //     // Clear the previous sales data
//     //     $('#salesContainer').empty();

//     //     // AJAX request to fetch sales data
//     //     $.ajax({
//     //         url: 'fetch_sales.php',  // Path to your PHP script
//     //         type: 'POST',
//     //         data: {
//     //             year: year,
//     //             month: month
//     //         },
//     //         dataType: 'json',
//     //         success: function (response) {
//     //             if (response.error) {
//     //                 alert(response.error);
//     //             } else {
//     //                 renderSalesData(response);
//     //             }
//     //         },
//     //         error: function (jqXHR, textStatus, errorThrown) {
//     //             console.log('AJAX Error: ' + textStatus + ': ' + errorThrown);
//     //         }
//     //     });
//     //     console.log("Year: " + year + ", Month: " + month);
//     // });

//     // Function to render sales data
//     function renderSalesData(response) {
//         if (response.sales && response.sales.length > 0) {
//             var html = '<table class="table table-bordered"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Products</th><th>Total Price</th><th>Order Status</th><th>Created At</th></tr></thead><tbody>';
            
//             // Loop through the sales data and display each row
//             response.sales.forEach(function (sale) {
//                 html += '<tr>';
//                 html += '<td>' + sale.id + '</td>';
//                 html += '<td>' + sale.name + '</td>';
//                 html += '<td>' + sale.email + '</td>';
//                 html += '<td>' + sale.products.replace(/<br>/g, "<br />") + '</td>';
//                 html += '<td>' + sale.total_price + '</td>';
//                 html += '<td>' + sale.order_status + '</td>';
//                 html += '<td>' + sale.created_at + '</td>';
//                 html += '</tr>';
//             });

//             html += '</tbody></table>';

//             // Render product summary
//             html += '<caption>Total Purchased Items</caption>';
//             html += '<table class="table table-bordered"><thead><tr><th>Product Name</th><th>Size</th><th>Total Number of Purchases</th></tr></thead><tbody>';
            
//             response.summary.forEach(function (product) {
//                 html += '<tr>';
//                 html += '<td>' + product.name + '</td>';
//                 html += '<td>' + product.size + '</td>';
//                 html += '<td>' + product.total + '</td>';
//                 html += '</tr>';
//             });

//             html += '</tbody></table>';

//             // Insert the new HTML into the container
//             $('#salesContainer').html(html);
//         }
//     }
// });
