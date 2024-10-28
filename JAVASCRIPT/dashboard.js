document.addEventListener("DOMContentLoaded", function () {
    const ctxLineChart = document.getElementById('myLineChart').getContext('2d');

    // Retrieve JSON data for daily sales and profit
    const dailySalesData = JSON.parse(document.getElementById('daily_sales_json').textContent);
    const dailyProfitData = JSON.parse(document.getElementById('daily_profit_json').textContent);
    const dailyLabels = Array.from({ length: 31 }, (_, i) => (i + 1).toString()); // Days of the month (1 to 31)

    // Initial data for Daily Sales Chart
    let dailyLineChartData = {
        labels: dailyLabels,  // This will be the days of the month
        datasets: [{
            label: 'Daily Sales',
            data: dailySalesData, // Data for daily sales
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }, {
            label: 'Daily Profit',
            data: dailyProfitData, // Data for daily profit
            borderColor: 'rgba(255, 159, 64, 1)',
            backgroundColor: 'rgba(255, 159, 64, 0.2)',
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    };

    // Chart Configuration for daily sales and profit
    const dailyLineConfig = {
        type: 'line',
        data: dailyLineChartData,
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
                        text: 'Days'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Sales / Profit'
                    },
                    beginAtZero: true
                }
            }
        }
    };

    // Create the chart
    let myLineChart = new Chart(ctxLineChart, dailyLineConfig);

    // Event Listeners for your existing buttons (optional)
    document.getElementById('daily-sales').addEventListener('click', function () {
        myLineChart.data.datasets[0].data = dailySalesData;
        myLineChart.data.labels = dailyLabels;
        myLineChart.data.datasets[0].label = 'Daily Sales';
        myLineChart.update();
    });

    document.getElementById('daily-profit').addEventListener('click', function () {
        myLineChart.data.datasets[1].data = dailyProfitData;
        myLineChart.data.labels = dailyLabels;
        myLineChart.data.datasets[1].label = 'Daily Profit';
        myLineChart.update();
    });
    // Pie Chart for Dashboard
    const ctxDashboardPieChart = document.getElementById('myPieChart').getContext('2d');
    const labelsDashboard = safeJSONParse('labels_json_dashboard', []);
    const pieChartDataDashboard = safeJSONParse('data_json_dashboard', []).map(Number);

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
                        padding: 5
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
