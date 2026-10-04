with open("dashboard.php", "r", encoding="utf-8") as f:
    text = f.read()

import re

old_logic = """            $stmtAllProjs->execute([$_SESSION['user_id'], $viewData['classroom_id']]);
            $viewData['availableProjects'] = $stmtAllProjs->fetchAll(PDO::FETCH_ASSOC);
            
            // Artificial Simulation for Demo: Ensure there are at least 5 projects displayed
            $mockProjectsPool = [
                ['id' => 991, 'name' => 'Library Management system', 'description' => 'A digital solution for campus library book tracking, issuing, and automated fine calculation.', 'active_members' => 3, 'my_status' => 'None'],
                ['id' => 992, 'name' => 'Hostel Management System', 'description' => 'Platform for room allocation, mess fee tracking, and hostel complaint logging.', 'active_members' => 3, 'my_status' => 'None'],
                ['id' => 993, 'name' => 'Placement management system', 'description' => 'Web portal to track upcoming campus drives, student eligibility, and interview schedules.', 'active_members' => 3, 'my_status' => 'Pending'],
                ['id' => 994, 'name' => 'Vehicle parking management System', 'description' => 'Automated parking slot allocation and campus entry tracking using RFID.', 'active_members' => 4, 'my_status' => 'None'],
                ['id' => 995, 'name' => 'Hospital Management System', 'description' => 'Centralized patient record management, appointment booking, and inventory system.', 'active_members' => 4, 'my_status' => 'None']
            ];
            
            $currentCount = count($viewData['availableProjects']);
            if ($currentCount < 5) {
                $needed = 5 - $currentCount;
                // Slice the required number of mock projects and merge them with the real ones
                $mockSlice = array_slice($mockProjectsPool, 0, $needed);
                $viewData['availableProjects'] = array_merge($viewData['availableProjects'], $mockSlice);
            }
        }
    }
}"""

new_logic = """            $stmtAllProjs->execute([$_SESSION['user_id'], $viewData['classroom_id']]);
            $viewData['availableProjects'] = $stmtAllProjs->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}"""

text = text.replace(old_logic, new_logic)

with open("dashboard.php", "w", encoding="utf-8") as f:
    f.write(text)

print("Removed mock marketplace data from production logic.")
