document.addEventListener("DOMContentLoaded", function () {
    const ctxLineChart = document.getElementById('myLineChart').getContext('2d');

    // Define month labels for Annual data
    const monthLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // Fetch monthly sales and profit data for Annual
    const monthlySalesData = JSON.parse(document.getElementById('monthly_sales_json').textContent || '[]');
    const monthlyProfitData = JSON.parse(document.getElementById('monthly_profit_json').textContent || '[]');

    // Fetch weekly sales and profit data for Daily Sales/Profit (7 days)
    const weeklySalesData = JSON.parse(document.getElementById('weekly_sales_json').textContent || '[]');
    const weeklyProfitData = JSON.parse(document.getElementById('weekly_profit_json').textContent || '[]');
    const weeklyLabels = JSON.parse(document.getElementById('weekly_labels_json').textContent || '[]');

    // Fetch annual and daily sales/profit data
    const annualSales = parseFloat(document.getElementById('annual_sales_json')?.textContent || 0);
    const annualProfit = parseFloat(document.getElementById('annual_profit_json')?.textContent || 0);

    // Initial data for the chart (set to daily sales and profit by default)
    const chartData = {
        labels: weeklyLabels.length > 0 ? weeklyLabels : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        datasets: [
            {
                label: 'Sales',
                data: weeklySalesData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 4,
                tension: 0.5,
                fill: true
            },
            {
                label: 'Profit',
                data: weeklyProfitData,
                borderColor: 'rgba(255, 159, 64, 1)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderWidth: 4,
                tension: 0.5,
                fill: true
            }
        ]
    };

    // Create the chart
    let myLineChart = new Chart(ctxLineChart, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Amount in ₱'
                    },
                    beginAtZero: true
                }
            }
        }
    });

    // Function to update the chart with specific data
    function updateChart(labels, dataSales = [], dataProfit = []) {
        myLineChart.data.labels = labels;
        myLineChart.data.datasets[0].data = dataSales.length > 0 ? dataSales : Array(labels.length).fill(null); // Set to null if data is empty
        myLineChart.data.datasets[1].data = dataProfit.length > 0 ? dataProfit : Array(labels.length).fill(null); // Set to null if data is empty
        myLineChart.update();
    }

    // Event listener for "Annual Sales" - to show monthly sales and profit data
    document.getElementById('annual-sales').addEventListener('click', function () {
        updateChart(monthLabels, monthlySalesData, monthlyProfitData); // Show both sales and profit for each month
    });

    // Event listener for "Annual Profit" - show monthly profit data only
    document.getElementById('annual-profit').addEventListener('click', function () {
        updateChart(monthLabels, Array(monthLabels.length).fill(null), monthlyProfitData); // Set sales data to null
    });

    // Event listener for "Daily Sales" - show daily sales for the last 7 days
    document.getElementById('daily-sales').addEventListener('click', function () {
        updateChart(weeklyLabels, weeklySalesData, Array(weeklyLabels.length).fill(null)); // Set profit data to null
    });

    // Event listener for "Daily Profit" - show daily profit for the last 7 days
    document.getElementById('daily-profit').addEventListener('click', function () {
        updateChart(weeklyLabels, Array(weeklyLabels.length).fill(null), weeklyProfitData); // Set sales data to null
    });

    // Pie Chart for Dashboard
    const ctxDashboardPieChart = document.getElementById('myPieChart').getContext('2d');
    const labelsDashboard = JSON.parse(document.getElementById('labels_json_dashboard').textContent || '[]');
    const pieChartDataDashboard = JSON.parse(document.getElementById('data_json_dashboard').textContent || '[]').map(Number);

    const pieDataDashboard = {
        labels: labelsDashboard,
        datasets: [{
            label: 'Top 10 Selling Products',
            data: pieChartDataDashboard,
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 159, 64, 0.6)',
                'rgba(199, 199, 199, 0.6)',
                'rgba(83, 102, 255, 0.6)',
                'rgba(50, 168, 82, 0.6)',
                'rgba(120, 46, 139, 0.6)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(199, 199, 199, 1)',
                'rgba(83, 102, 255, 1)',
                'rgba(50, 168, 82, 1)',
                'rgba(120, 46, 139, 1)'
            ],
            borderWidth: 1
        }]
    };

    const pieConfigDashboard = {
        type: 'pie',
        data: pieDataDashboard,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 20,
                        padding: 20,
                        font: {
                            size: 14, // Set font size here for legend labels
                            weight: 'bold' // Set font weight here for legend labels
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.raw || context.dataset.data[context.dataIndex];
                            const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    },
                    titleFont: {
                        size: 16, // Set font size here for tooltip title
                        weight: 'bold' // Set font weight here for tooltip title
                    },
                    bodyFont: {
                        size: 12, // Set font size here for tooltip body
                    }
                }
            },
            layout: {
                padding: {
                    right: 10
                }
            }
        }
    };
    

    const myDashboardPieChart = new Chart(ctxDashboardPieChart, pieConfigDashboard);
});

