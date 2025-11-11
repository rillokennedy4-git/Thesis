<!-- Bar Chart -->
<div class="col-md-6">
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar me-1"></i>  Complete and Incomplete 
            <select id="recordStatusYearDropdown" class="form-select" style="width: auto; display: inline-block;">
                <option value="0">All Years</option>
                <?php foreach ($studentsPerYear as $year): ?>
                    <option value="<?php echo $year['year_id']; ?>"><?php echo $year['year']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="card-body chart-container1">
            <canvas id="recordStatusBarChart"></canvas>
        </div>
        <div class="card-footer text-muted">
            
            </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctxBar = document.getElementById('recordStatusBarChart').getContext('2d');
        let recordStatusBarChart;

        function createBarChart(data) {
            if (recordStatusBarChart) recordStatusBarChart.destroy();

            const labels = data.map(item => item.status);
            const chartData = data.map(item => item.student_count);
            const colors = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 60%)`);

            recordStatusBarChart = new Chart(ctxBar, {
                type: 'bar',
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
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true },
                        datalabels: {
                            display: true,
                            color: 'black',
                            anchor: 'end',
                            align: 'top',
                            formatter: (value) => value
                        }
                    },
                    scales: {
                        x: { title: { display: true, text: "Record Status" } },
                        y: { beginAtZero: true, title: { display: true, text: "Number of Students" } }
                    }
                }
            });
        }

        createBarChart(<?php echo json_encode($studentsPerRecordStatusAllYears); ?>);

        document.getElementById('recordStatusYearDropdown').addEventListener('change', function () {
            fetch(`?year_id=${this.value}&chart=record_status`).then(res => res.json()).then(data => {
                createBarChart(data);
            });
        });
    });
</script>
