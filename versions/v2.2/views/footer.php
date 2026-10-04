
    

    

    <script>
        window.PMS_IS_READONLY = <?php echo json_encode(!empty($viewData['isTeacherDrilldown'])); ?>;
        window.PMS_CLASSROOM_ID = "<?php echo isset($viewData['classroom_id']) ? urlencode($viewData['classroom_id']) : ''; ?>";
        window.PMS_TEAM_ROSTER = <?php echo json_encode($viewData['teamRoster'] ?? []); ?>;
    </script>
    <script src="assets/js/dashboard.js?v=<?php echo time(); ?>"></script>

</body>

</html>