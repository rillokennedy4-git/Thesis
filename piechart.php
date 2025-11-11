<!-- Pie Chart -->
<div class="col-md-6">
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-pie me-1"></i> New Student and Transferree 
            <select id="statusYearDropdown" class="form-select" style="width: auto; display: inline-block;">
                <option value="0">All Years</option>
                <?php foreach ($studentsPerYear as $year): ?>
                    <option value="<?php echo $year['year_id']; ?>"><?php echo $year['year']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="card-body chart-container1">
            <canvas id="myPieChart"></canvas>
        </div>
        <div class="card-footer text-muted">
            
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctxPie = document.getElementById('myPieChart').getContext('2d');
        let studentsStatusChart;

        function updateChart(chart, ctx, data, type) {
            if (chart) chart.destroy();

            const labels = data.map(item => item.status);
            const chartData = data.map(item => item.student_count);
            const colors = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 60%)`);

            return new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%' // Adjust this value to make the ring thinner (e.g., '70%', '85%', etc.)
                }
            });
        }

        studentsStatusChart = updateChart(null, ctxPie, <?php echo json_encode($studentsPerStatusAllYears); ?>, 'doughnut');

        document.getElementById('statusYearDropdown').addEventListener('change', function () {
            fetch(`?year_id=${this.value}`).then(res => res.json()).then(data => {
                studentsStatusChart = updateChart(studentsStatusChart, ctxPie, data, 'doughnut');
            });
        });
    });
</script>

