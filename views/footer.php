
    

    

    <script>
        window.PMS_IS_READONLY = <?php echo json_encode(!empty($viewData['isTeacherDrilldown'])); ?>;
        window.PMS_IS_DEMO = <?php echo json_encode($viewData['isDemo'] ?? false); ?>;
        window.PMS_CLASSROOM_ID = "<?php echo isset($viewData['classroom_id']) ? urlencode($viewData['classroom_id']) : ''; ?>";
        window.PMS_MOCK_GROUPS = <?php echo json_encode($viewData['mockProjectGroupsRaw'] ?? []); ?>;
        window.PMS_TEAM_ROSTER = <?php echo json_encode($viewData['teamRoster'] ?? []); ?>;
    </script>
    <script src="assets/js/dashboard.js?v=<?php echo time(); ?>"></script>

</body>

</html>