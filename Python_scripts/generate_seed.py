import pandas as pd
import re

# 1. Read and clean the data
df_a = pd.read_excel('MIN PROJECT A-B Section.xlsx', sheet_name='A sec', header=6)
df_a = df_a.rename(columns={'GROUP MEMBERS': 'STUDENT_NAME', 'STUDENT USN': 'USN', 'PROJECT NAME': 'PROJECT', 'Guide Name': 'GUIDE', 'GROUP NO': 'GROUP_NO'})
df_a = df_a[['GROUP_NO', 'STUDENT_NAME', 'USN', 'PROJECT', 'GUIDE']]
df_a['STUDENT_NAME'] = df_a['STUDENT_NAME'].astype(str).str.strip()
df_a = df_a[~df_a['STUDENT_NAME'].str.contains('not given|nan', case=False, na=False)]

df_b = pd.read_excel('MIN PROJECT A-B Section.xlsx', sheet_name='B sec', header=2)
df_b = df_b.rename(columns={'NAMES': 'STUDENT_NAME', 'USN': 'USN', 'Project title': 'PROJECT', 'GUIDE NAME': 'GUIDE', 'Group No': 'GROUP_NO'})
df_b = df_b[['GROUP_NO', 'STUDENT_NAME', 'USN', 'PROJECT', 'GUIDE']]
df_b['STUDENT_NAME'] = df_b['STUDENT_NAME'].astype(str).str.strip()
df_b = df_b[~df_b['STUDENT_NAME'].str.contains('not given|nan', case=False, na=False)]

df = pd.concat([df_a, df_b], ignore_index=True)

# Forward fill groups, projects, and guides
df['GROUP_NO'] = df['GROUP_NO'].ffill()
df['PROJECT'] = df['PROJECT'].ffill()
df['GUIDE'] = df['GUIDE'].ffill()

# Drop rows that don't have a student (like empty trailing rows)
df = df.dropna(subset=['STUDENT_NAME'])

# Clean guide names (remove Prof, Dr, Prof., Dr.)
def clean_guide_name(name):
    if pd.isna(name): return 'Unassigned'
    name = str(name).strip()
    name = re.sub(r'^(Prof\.?|Dr\.?)\s+', '', name, flags=re.IGNORECASE)
    return name

df['GUIDE'] = df['GUIDE'].apply(clean_guide_name)

# 2. Hash the standard password ('password123')
hashed_pw = '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq'

sql_statements = []
sql_statements.append('-- ==========================================')
sql_statements.append('-- PMS DEMO SEED DATA')
sql_statements.append('-- All accounts use password: password123')
sql_statements.append('-- ==========================================\n')
sql_statements.append('USE pms;\n')

# 3. Process Users
users = {}
user_id_counter = 1

def add_user(username, role=None):
    global user_id_counter
    if username not in users:
        users[username] = user_id_counter
        # Escape quotes in names
        safe_name = str(username).replace("'", "''")
        sql = f"INSERT INTO users (id, username, password, role) VALUES ({user_id_counter}, '{safe_name}', '{hashed_pw}', NULL);"
        sql_statements.append(sql)
        user_id_counter += 1
    return users[username]

# Add a coordinator
coord_id = add_user('Coordinator Admin')

# Extract unique guides and students
guides = df['GUIDE'].unique()
for g in guides:
    add_user(g)

for s in df['STUDENT_NAME'].unique():
    add_user(s)

sql_statements.append('\n-- 4. Create Classroom')
class_id = 1
sql_statements.append(f"INSERT INTO classrooms (id, name, created_by, invite_code, requires_usn) VALUES ({class_id}, '5th Semester CS (A & B Section)', {coord_id}, 'VTU26X', 1);")

# Add coordinator to classroom
sql_statements.append(f"INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES ({class_id}, {coord_id}, 'Admin', NULL);")

sql_statements.append('\n-- 5. Add Guides and Students to Classroom')
# Guides as Admins
for g in guides:
    uid = users[g]
    sql_statements.append(f"INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES ({class_id}, {uid}, 'Admin', NULL);")

# Students as Team Members
for idx, row in df.iterrows():
    uid = users[row['STUDENT_NAME']]
    usn = str(row['USN']).strip()
    sql_statements.append(f"INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES ({class_id}, {uid}, 'Team Member', '{usn}');")

sql_statements.append('\n-- 6. Create Projects and Assign Members')
project_id_counter = 1
grouped = df.groupby('GROUP_NO')

for group_no, group_df in grouped:
    proj_name = str(group_df.iloc[0]['PROJECT']).replace("'", "''")
    if pd.isna(group_df.iloc[0]['PROJECT']):
        proj_name = f'Group {int(group_no)} Project'
    guide_name = group_df.iloc[0]['GUIDE']
    guide_id = users[guide_name]
    
    # Insert project
    sql_statements.append(f"INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES ({project_id_counter}, {class_id}, '{proj_name}', 'Mini project for 5th semester.', {coord_id}, {guide_id});")
    
    # Insert members
    is_first = True
    for idx, row in group_df.iterrows():
        uid = users[row['STUDENT_NAME']]
        leader_flag = 1 if is_first else 0
        sql_statements.append(f"INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES ({project_id_counter}, {uid}, {leader_flag}, 'Active');")
        is_first = False
        
    # Generate some dummy tasks for the project
    sql_statements.append(f"INSERT INTO tasks (project_id, title, description, status, milestone) VALUES ({project_id_counter}, 'Literature Survey', 'Read 3 base papers', 'done', 'Synopsis');")
    sql_statements.append(f"INSERT INTO tasks (project_id, title, description, status, milestone) VALUES ({project_id_counter}, 'Architecture Diagram', 'Draft the system architecture', 'inprogress', 'Synopsis');")
    sql_statements.append(f"INSERT INTO tasks (project_id, title, description, status, milestone) VALUES ({project_id_counter}, 'Set up database', 'Create tables based on schema', 'todo', 'Phase 1');")
    
    project_id_counter += 1

with open('demo_seed.sql', 'w', encoding='utf-8') as f:
    f.write('\n'.join(sql_statements))

print('SUCCESS: demo_seed.sql generated with ' + str(len(sql_statements)) + ' queries.')
