<?php
require_once __DIR__ . '/includes/auth.php';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AttendTrack — Attendance Monitoring System</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ════════════════════════════════════════════════════════════
     AUTH SCREEN
═════════════════════════════════════════════════════════════ -->
<div id="auth-screen">
  <div class="auth-box">
    <div class="auth-logo">Attend<span>Track</span></div>
    <p class="auth-sub">Classroom Attendance Monitoring System</p>

    <div class="auth-tabs">
      <button class="auth-tab active" data-tab="login">Log In</button>
      <button class="auth-tab" data-tab="register">Register</button>
    </div>

    <!-- Login Form -->
    <form id="form-login">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@school.edu" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem">
        <i class="ti ti-login"></i> Log In
      </button>
    </form>

    <!-- Register Form -->
    <form id="form-register" style="display:none">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="Juan Dela Cruz" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="teacher@school.edu" required>
      </div>
      <div class="form-group">
        <label>Password <span style="color:var(--muted2);font-size:12px">(min. 6 characters)</span></label>
        <input type="password" name="password" class="form-control" placeholder="Create a password" required minlength="6">
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem">
        <i class="ti ti-user-plus"></i> Create Account
      </button>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MAIN APP
═════════════════════════════════════════════════════════════ -->
<div id="app">

  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-logo">Attend<span>Track</span></div>
    <div class="nav-right">
      <div class="nav-teacher">Logged in as <strong id="teacher-name"></strong></div>
      <button class="btn btn-ghost btn-sm" id="btn-logout">
        <i class="ti ti-logout"></i> Log Out
      </button>
    </div>
  </nav>

  <!-- Main Layout -->
  <div class="main-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
      <span class="sidebar-label">Navigation</span>
      <button class="sidebar-item active" data-section="sec-dashboard">
        <i class="ti ti-layout-dashboard"></i> Dashboard
      </button>
      <button class="sidebar-item" data-section="sec-year-levels">
        <i class="ti ti-school"></i> Year Levels
      </button>
      <button class="sidebar-item" data-section="sec-attendance">
        <i class="ti ti-calendar-check"></i> Take Attendance
      </button>

      <span class="sidebar-label" style="margin-top:auto">Account</span>
      <button class="sidebar-item" onclick="document.getElementById('btn-logout').click()">
        <i class="ti ti-logout"></i> Log Out
      </button>
    </aside>

    <!-- Content Area -->
    <main class="content">

      <!-- ── DASHBOARD ── -->
      <section id="sec-dashboard" class="content-section active">
        <div class="page-header">
          <div>
            <div class="page-title">Dashboard</div>
            <div class="page-sub">Overview of your classes</div>
          </div>
        </div>

        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-label"><i class="ti ti-school"></i> Year Levels</div>
            <div class="stat-value purple" id="dash-yl-count">—</div>
          </div>
          <div class="stat-card">
            <div class="stat-label"><i class="ti ti-users"></i> Total Sections</div>
            <div class="stat-value green" id="dash-sec-count">—</div>
          </div>
        </div>

        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:1.25rem">
          <div style="font-weight:600;font-size:14px;margin-bottom:1rem;color:var(--muted)">
            <i class="ti ti-list" style="margin-right:6px"></i>YOUR YEAR LEVELS
          </div>
          <div id="dash-yl-list"></div>
          <div style="margin-top:1rem">
            <button class="btn btn-ghost btn-sm" onclick="document.querySelector('[data-section=sec-year-levels]').click()">
              <i class="ti ti-arrow-right"></i> Manage Year Levels
            </button>
          </div>
        </div>
      </section>

      <!-- ── YEAR LEVELS ── -->
      <section id="sec-year-levels" class="content-section">
        <div class="page-header">
          <div>
            <div class="page-title">Year Levels</div>
            <div class="page-sub">Manage your grade levels and sections</div>
          </div>
          <button class="btn btn-primary" id="btn-add-yl">
            <i class="ti ti-plus"></i> Add Year Level
          </button>
        </div>
        <div class="cards-grid" id="yl-cards">
          <div class="loading"><i class="ti ti-loader-2"></i></div>
        </div>
      </section>

      <!-- ── SECTIONS (inside a year level) ── -->
      <section id="sec-sections" class="content-section">
        <div class="breadcrumb" id="sec-sections-breadcrumb"></div>
        <div class="page-header">
          <div>
            <div class="page-title" id="sec-sections-title">Sections</div>
            <div class="page-sub">Manage sections in this year level</div>
          </div>
          <button class="btn btn-primary" id="btn-add-section" data-ylid="">
            <i class="ti ti-plus"></i> Add Section
          </button>
        </div>
        <div class="cards-grid" id="sections-cards">
          <div class="loading"><i class="ti ti-loader-2"></i></div>
        </div>
      </section>

      <!-- ── STUDENTS (inside a section) ── -->
      <section id="sec-students" class="content-section">
        <div class="breadcrumb" id="sec-students-breadcrumb"></div>
        <div class="page-header">
          <div>
            <div class="page-title" id="sec-students-title">Students</div>
            <div class="page-sub" id="sec-students-subject" style="display:flex;align-items:center;gap:6px">
              <i class="ti ti-book" style="font-size:13px"></i>
            </div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-ghost" id="btn-view-report" data-secid="">
              <i class="ti ti-chart-bar"></i> Reports
            </button>
            <button class="btn btn-success" id="btn-take-attendance" data-secid="">
              <i class="ti ti-calendar-check"></i> Take Attendance
            </button>
            <button class="btn btn-primary" id="btn-add-student" data-secid="">
              <i class="ti ti-user-plus"></i> Add Student
            </button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Student</th>
                <th>Added On</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="students-tbody">
              <tr><td colspan="4" class="loading"><i class="ti ti-loader-2"></i></td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── TAKE ATTENDANCE ── -->
      <section id="sec-attendance" class="content-section">
        <div class="page-header">
          <div>
            <div class="page-title">Take Attendance</div>
            <div class="page-sub">Record daily attendance for a section</div>
          </div>
        </div>

        <!-- Section picker (when coming from sidebar) -->
        <div id="att-section-picker-area" style="display:none">
          <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:1.5rem;max-width:500px">
            <div style="font-weight:600;margin-bottom:1rem;color:var(--text)">Select a Section</div>
            <div class="form-group">
              <label>Year Level</label>
              <select id="att-pick-yl" class="form-control">
                <option value="">— Select Year Level —</option>
              </select>
            </div>
            <div class="form-group">
              <label>Section</label>
              <select id="att-pick-section" class="form-control">
                <option value="">— Select Section —</option>
              </select>
            </div>
            <button class="btn btn-primary" id="btn-go-attendance">
              <i class="ti ti-arrow-right"></i> Continue
            </button>
          </div>
        </div>

        <!-- Attendance form -->
        <div id="att-main-area" style="display:none">
          <div style="margin-bottom:1.25rem">
            <div style="font-size:1.1rem;font-weight:600;color:var(--text)" id="att-section-name"></div>
          </div>

          <div class="date-picker-row">
            <span class="date-label"><i class="ti ti-calendar" style="margin-right:4px"></i>Date:</span>
            <input type="date" id="att-date" class="form-control" style="width:auto">
            <div style="display:flex;gap:6px;margin-left:auto">
              <button class="btn btn-ghost btn-sm" id="btn-mark-all-present">
                <i class="ti ti-check"></i> All Present
              </button>
              <button class="btn btn-ghost btn-sm" id="btn-mark-all-absent">
                <i class="ti ti-x"></i> All Absent
              </button>
            </div>
          </div>

          <!-- Legend -->
          <div style="display:flex;gap:12px;margin-bottom:1rem;flex-wrap:wrap">
            <span style="font-size:12px;color:var(--muted)">Legend:</span>
            <span class="badge badge-present">P = Present</span>
            <span class="badge badge-absent">A = Absent</span>
            <span class="badge badge-late">L = Late</span>
            <span class="badge badge-excused">E = Excused</span>
          </div>

          <div class="table-wrap" style="margin-bottom:1.25rem">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="att-form-tbody">
                <tr><td colspan="3" class="loading"><i class="ti ti-loader-2"></i></td></tr>
              </tbody>
            </table>
          </div>

          <div style="display:flex;gap:10px;align-items:center">
            <button class="btn btn-primary" id="btn-save-attendance">
              <i class="ti ti-device-floppy"></i> Save Attendance
            </button>
            <span style="font-size:13px;color:var(--muted)">Changes auto-update if a session for this date already exists.</span>
          </div>
        </div>
      </section>

      <!-- ── REPORT ── -->
      <section id="sec-report" class="content-section">
        <div class="breadcrumb" id="sec-report-breadcrumb"></div>
        <div class="page-header">
          <div>
            <div class="page-title">Attendance Report</div>
            <div class="page-sub" id="rep-section-name"></div>
          </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-label">Total Sessions</div>
            <div class="stat-value purple" id="rep-stat-sessions">—</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Students</div>
            <div class="stat-value amber" id="rep-stat-students">—</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Avg. Attendance Rate</div>
            <div class="stat-value green" id="rep-stat-avg">—</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">For Dropping (&lt;70%)</div>
            <div class="stat-value red" id="rep-stat-drops">—</div>
          </div>
        </div>

        <!-- Student Report Table -->
        <div style="font-weight:600;font-size:14px;color:var(--muted);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:.06em">
          <i class="ti ti-users" style="margin-right:6px"></i>Per-Student Summary
        </div>
        <div class="table-wrap" style="margin-bottom:1.75rem">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Student</th>
                <th style="color:var(--success)">Present</th>
                <th style="color:var(--danger)">Absent</th>
                <th style="color:var(--late)">Late</th>
                <th style="color:var(--info)">Excused</th>
                <th>Rate</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="rep-tbody">
              <tr><td colspan="8" class="loading"><i class="ti ti-loader-2"></i></td></tr>
            </tbody>
          </table>
        </div>

        <!-- Sessions Log -->
        <div style="font-weight:600;font-size:14px;color:var(--muted);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:.06em">
          <i class="ti ti-calendar" style="margin-right:6px"></i>Attendance Log
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Total</th>
                <th style="color:var(--success)">Present</th>
                <th style="color:var(--danger)">Absent</th>
                <th style="color:var(--late)">Late</th>
                <th style="color:var(--info)">Excused</th>
              </tr>
            </thead>
            <tbody id="log-tbody">
              <tr><td colspan="6" class="loading"><i class="ti ti-loader-2"></i></td></tr>
            </tbody>
          </table>
        </div>
      </section>

    </main><!-- /content -->
  </div><!-- /main-layout -->
</div><!-- /app -->

<!-- Toast Container -->
<div id="toast-container"></div>

<script src="assets/js/app.js"></script>
</body>
</html>
