# Schola Timetabler Moodle Plugin — Administrator & User Guide

Welcome to the **Schola Timetabler** administrator user guide. This document provides step-by-step instructions for Moodle Administrators, Academic Schedulers, and Department Heads to set up campus venues, configure time slots, protect break windows, and generate conflict-free weekly timetables.

---

## 📌 Table of Contents

1. [Introduction](#introduction)
2. [Quickstart Checklist](#quickstart-checklist)
3. [5-Step Timetabling Workflow](#5-step-timetabling-workflow)
   - [Step 1: Campus Venues & Room Management](#step-1-campus-venues--room-management)
   - [Step 2: Profiles & Time Slot Configuration](#step-2-profiles--time-slot-configuration)
   - [Step 3: Tea Break & Lunch Window Protection](#step-3-tea-break--lunch-window-protection)
   - [Step 4: Generating Timetables (Class vs Exam)](#step-4-generating-timetables-class-vs-exam)
   - [Step 5: Timetable Studio (View, Filter, Export & Print)](#step-5-timetable-studio-view-filter-export--print)
4. [CSV Batch Import Specifications](#csv-batch-import-specifications)
5. [Local Solver Setup](#local-solver-setup)
6. [Frequently Asked Questions (FAQ) & Troubleshooting](#frequently-asked-questions-faq--troubleshooting)
7. [Support & Assistance](#support--assistance)

---

## 🌟 Introduction

**Schola Timetabler** is an automated scheduling solution integrated directly into Moodle. It eliminates manual scheduling conflicts for:
* **Weekly Class Schedules**: Recurring lectures, laboratory practicals, and tutorial sessions.
* **Examination Windows**: Midterm and final exam matrix scheduling with room capacity enforcement.

---

## 📋 Quickstart Checklist

Before generating your first timetable, ensure the following steps are completed:
- [x] **Rooms**: Define at least 3 campus venues with seating capacities.
- [x] **Slots**: Set up weekly time period slots (or apply a preset profile).
- [x] **Breaks**: Configure morning tea breaks and lunch windows.
- [x] **Courses**: Ensure courses are assigned to categories/departments in Moodle.

---

## 🚀 5-Step Timetabling Workflow

### Step 1: Campus Venues & Room Management
Navigate to **Schola Timetabler ➔ Rooms**.
1. **Add Rooms**: Click **Add New Room** and enter a **Room Name** (e.g. *Lecture Hall 101*, *Science Lab B*) and **Seating Capacity**.
2. **Laboratory Toggle**: Check the *Laboratory / Computer Studio* toggle for rooms requiring specialized equipment or computers.
3. **Virtual Spaces**: Rooms named with *Online*, *Virtual*, or *Zoom* are automatically flagged for remote delivery.

### Step 2: Profiles & Time Slot Configuration
Navigate to **Schola Timetabler ➔ Profiles & Slots**.
1. **Apply Presets**: Choose a schedule profile (*University Standard*, *3-Hour Block*, *High School 45-min*) to auto-populate period slots.
2. **Custom Slots**: Manually add specific day and time combinations (e.g., *Monday 08:00–09:30*).
3. **Batch Import**: Upload a CSV file to import all institutional time slots at once.

### Step 3: Tea Break & Lunch Window Protection
Navigate to **Schola Timetabler ➔ Breaks**.
1. **Define Break Windows**: Set institutional morning tea breaks (e.g., *10:30–11:00*) and lunch break windows (e.g., *13:00–14:00*).
2. **Conflict Avoidance**: The solver automatically locks these period slots as occupied blockouts so no lectures or exams are assigned during break times.

### Step 4: Generating Timetables (Class vs Exam)
Navigate to **Schola Timetabler ➔ Timetables** and click **Generate Timetable**.
1. **Title**: Enter a descriptive name (e.g., *Semester I 2026 Schedule*, *Final Exam Matrix*).
2. **Profile / Type**:
   - **Regular Class Schedule**: For weekly recurring course lectures and labs.
   - **Examination Schedule**: For midterm and final exam assessment blocks.
3. **Scope**: Select **Entire Institution** or filter by a specific **Department / Course Category**.
4. **Generation & Conflict Mode**:
   - **Save as Named Version**: Creates a new side-by-side timetable version without overwriting existing schedules.
   - **Overwrite ALL Timetables**: Replaces existing timetables of the selected type with the newly generated solution.
   - **Append Mode**: Keeps active schedules locked and schedules new courses around them.

### Step 5: Timetable Studio (View, Filter, Export & Print)
Navigate to **Schola Timetabler ➔ Timetables**.
- **Filter Grids**: Switch view perspectives by **Department**, **Campus Venue**, or **Instructor**.
- **CSV Export**: Download full schedule assignments for institutional record-keeping.
- **Print PDF**: Generate clean, printable schedule grids for posting on campus notice boards.

---

## 📊 CSV Batch Import Specifications

### 1. Rooms CSV Import (`Rooms ➔ Import CSV`)
Prepare a CSV file with the following column headers:
```csv
Name, Capacity, Is Lab
Auditorium A, 300, 0
Physics Computer Lab, 35, 1
Virtual Zoom Room 1, 500, 0
```

### 2. Time Slots CSV Import (`Slots ➔ Import CSV`)
Prepare a CSV file with the following column headers:
```csv
dayofweek, starttime, endtime, type
1, 08:00, 09:30, class
1, 09:30, 11:00, class
2, 09:00, 12:00, exam
```
*Note: `dayofweek` can be numbers (1=Monday .. 7=Sunday) or day names (e.g. `Mon`, `Tuesday`). Supported `type` values are `class`, `lab`, `break`, or `exam`.*

---

## ⚡ Local Solver Setup

The plugin runs entirely on your Moodle server. There is no external license or cloud service to configure.

1. Install the plugin in your local Moodle instance.
2. Configure rooms, slots, breaks, and course categories from the plugin pages.
3. Generate timetables directly from the built-in solver engine.

---

## ❓ Frequently Asked Questions (FAQ) & Troubleshooting

#### Q1: What happens if there are not enough rooms for all courses?
*The solver will report an error notice asking you to add more rooms or extend time period slots.*

#### Q2: How does the solver prevent teacher double-booking?
*The solver tracks teacher assignments across all schedules and ensures no teacher is scheduled in two places at the same time.*

#### Q3: Can I maintain draft timetables alongside active ones?
*Yes! Select **Save as Named Version** when generating timetables to compare different options side-by-side.*

---

## 📞 Support & Assistance

- **Support Email**: [wanyonyi.d.emanuel@gmail.com](mailto:wanyonyi.d.emanuel@gmail.com)
- **Issue Tracker**: [https://github.com/ewanyonyi/moodle-local_schola_timetabler/issues](https://github.com/ewanyonyi/moodle-local_schola_timetabler/issues)
