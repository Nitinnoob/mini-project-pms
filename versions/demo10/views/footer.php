
    

    

    <script>
        window.PMS_IS_DEMO = <?php echo json_encode($isDemo ?? false); ?>;
        window.PMS_CLASSROOM_ID = "<?php echo isset($classroom_id) ? urlencode($classroom_id) : ''; ?>";
        window.PMS_MOCK_GROUPS = <?php echo json_encode($mockProjectGroupsRaw ?? []); ?>;
        window.PMS_TEAM_ROSTER = <?php echo json_encode($teamRoster ?? []); ?>;
    </script>
    <script src="assets/js/dashboard.js"></script>

</body>

</html>