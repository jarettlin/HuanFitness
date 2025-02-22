<?php
session_start();
include('connection.php');

if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit;
}

$custId = $_SESSION['userid'];
$error = '';
$success = '';

// Check if there are messages in the session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); // Clear session message after using
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']); // Clear session message after using
}

// Add new exercise routine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_exercise'])) {
    $category = $_POST['category'];
    $duration = $_POST['duration'] === 'custom' ? $_POST['custom_duration'] : $_POST['duration'];
    $recordDate = $_POST['recordDate'];

    if ($category && $duration && $recordDate) {
        $query = "INSERT INTO exerciseRoutine (custId, category, duration, recordDate) VALUES ('$custId', '$category', '$duration', '$recordDate')";
        if (mysqli_query($conn, $query)) {
            $success = "Exercise routine added successfully!";
        } else {
            $error = "Error adding exercise routine: " . mysqli_error($conn);
        }
    } else {
        $error = "Please enter all fields!";
    }
}

// Update exercise routine
if (isset($_POST['update_exercise'])) {
    $id = $_POST['id'];
    $category = $_POST['category'];
    $duration = $_POST['duration'];
    $recordDate = $_POST['recordDate'];

    if ($category && $duration && $recordDate) {
        $query = "UPDATE exerciseRoutine SET category='$category', duration='$duration', recordDate='$recordDate' WHERE id='$id' AND custId='$custId'";
        if (mysqli_query($conn, $query)) {
            $success = "Exercise routine updated successfully!";
        } else {
            $error = "Error updating exercise routine: " . mysqli_error($conn);
        }
    } else {
        $error = "Please enter all fields!";
    }
}

// Delete exercise routine 
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM exerciseRoutine WHERE id = '$id' AND custId='$custId'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Record deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting the record!";
    }
    header("Location: exerciseRoutine.php"); // Redirect to clear POST data
    exit;
}

// Search for records
$searchType = '';
$searchValue = '';
if (isset($_POST['search'])) {
    $searchType = $_POST['searchType'];
    $searchValue = $_POST['searchValue'];

    switch ($searchType) {
        case 'Running':
        case 'Jogging':
        case 'Swimming':
        case 'Cycling':
        case 'Strength Training':
        case 'Yoga':
        case 'Walking':
        case 'Dancing':
        case 'Pilates':
            $query = "SELECT * FROM exerciseroutine WHERE custId = '$custId' AND category = '$searchType' ORDER BY recordDate DESC";
            break;
        case 'last_week':
            $startOfLastWeek = date('Y-m-d', strtotime('last Sunday -1 week')); // Last week's Sunday
            $endOfLastWeek = date('Y-m-d', strtotime('last Saturday')); // Last week's Saturday
            $query = "SELECT * FROM exerciseroutine WHERE custId = '$custId' AND recordDate BETWEEN '$startOfLastWeek' AND '$endOfLastWeek' ORDER BY recordDate DESC";
            break;
        case 'last_month':
            $startOfLastMonth = date('Y-m-01', strtotime('first day of last month')); // First day of last month
            $endOfLastMonth = date('Y-m-t', strtotime('last day of last month')); // Last day of last month
            $query = "SELECT * FROM exerciseroutine WHERE custId = '$custId' AND recordDate BETWEEN '$startOfLastMonth' AND '$endOfLastMonth' ORDER BY recordDate DESC";
            break;
        case 'date':
            $query = "SELECT * FROM exerciseroutine WHERE custId = '$custId' AND recordDate = '$searchValue' ORDER BY recordDate DESC";
            break;
        default:
            $query = "SELECT * FROM exerciseroutine WHERE custId = '$custId' ORDER BY recordDate DESC";
    }

} else {
    $query = "SELECT * FROM exerciseroutine WHERE custId = '$custId' ORDER BY recordDate DESC";
}
$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Exercise Routine</title>
    <link rel="stylesheet" type="text/css" href="general.css">
    <style>
        .navbar {
            display: flex;
            justify-content: center;
            background: linear-gradient(145deg, #f3e1f1, #d1c2e2);
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            text-decoration: none;
            padding: 10px 20px;
            color: #555;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .navbar a:hover {
            background: rgba(200, 200, 255, 0.1);
            color: #6d5acf;
        }

        .navbar .active {
            background: rgba(200, 150, 255, 0.2);
            color: #7a5dd6;
        }

        .navbar .active:hover {
            background: rgba(200, 150, 255, 0.3);
        }

        /* Style for the select dropdown */
        .modal select,
        form select {
            width: 100%;
            padding: 15px;
            margin-top: 5px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            box-sizing: border-box;
            font-size: 14px;
            font-family: inherit;
            background-color: #fff;
            color: #333;
            appearance: none;
            /* Remove default arrow for a custom one */
            background-image: url("dropdown-icon.png");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }

        .action-button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 14px;
            margin: 0 5px;
        }

        .update-button {
            background-color: #4CAF50;
            color: white;
        }

        .update-button:hover {
            background-color: #45a049;
        }

        .delete-button {
            background-color: #f44336;
            color: white;
        }

        .delete-button:hover {
            background-color: #e53935;
        }

        #recordDate{
            margin-bottom: 30px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100%;
            max-width: 450px;
            border-radius: 10px;
        }

        .no-result {
            width: 300px;
        }

        .modal .no-result {
            height: 150px;
        }

        .modal-content .no-result {
            height: 150px;
            width: 450px;
            text-align: center;
            padding-top: 30px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            float: right;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        h1{
            padding: 20px;
        }
        /* Form card styling */
        .form-card {
            max-width: 650px;
            margin: 20px;
            padding: 30px;
            background-color: #ddd5f3;
            border-radius: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .add-button {
            background-image: url("gradient.png");
            color: #ffffff;
            border-radius: 15px;
            padding: 13px;
        }

        .add-button:hover {
            background: linear-gradient(145deg, #4b0082, #301934);
        }

        .button-container {
            display: flex;
            gap: 10px;
        }

        .reset-button {
            height: 40px;
            width: 70px;
            justify-content: center;
        }

        .search-button {
            background-image: url("gradient.png");
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
            height: 40px;
        }

        .search-button:hover {
            background: linear-gradient(145deg, #4b0082, #301934);
        }

        .search-button img {
            width: 20px;
            height: 20px;
        }

        .blue-search-button {
            margin-top: 15px;
        }

        .modal input[type="date"],
        .modal input[type="number"],
        .modal select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 20px;
            box-sizing: border-box;
        }

        .modal-content input[type="submit"] {
            background-color: #007bff;
            border-radius: 20px;
            display: inline-block;
        }

        .modal-content input[type="submit"]:hover {
            background-color: #0056b3;
        }

        #custom-duration {
            border: none;
            border-bottom: 1px solid #000;
        }

        #custom-duration:focus {
            outline: none;
            border-bottom: 2px solid #333;
        }

        #message-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .message {
            display: none;
            padding: 15px;
            margin: 5px;
            border-radius: 5px;
            color: white;
            opacity: 0;
            transition: opacity 0.5s;
            text-align: center;
            min-width: 200px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        form input[type="date"], form select{
            width: calc(100% - 20px);
            padding: 13px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 15px;
            box-sizing: border-box;
        }

        form input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 15px;
            cursor: pointer;
        }

        form input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div id="message-container"></div>
    <div class="container">
        <div class="navbar">
            <a href="customer_home.php">Home</a>
            <a href="bodyWeight.php">Body Weight</a>
            <a href="exerciseRoutine.php" class="active">Exercise Routine</a>
            <a href="waterConsumption.php">Water Consumption</a>
            <a href="bookingConsultant.php">Booking Consultant</a>
            <a href="logout.php">Logout</a>
        </div>

        <h1>Exercise Routine</h1>

        <div class="form-card">
            <form method="POST" action="">
                <label for="category"></label>

                <label for="recordDate">Date:</label>
                <input type="date" id="recordDate" name="recordDate" required><br>

                <label for="duration">Duration (Minute):</label><br><br>
                <div id="duration-options">
                    <label><input type="radio" name="duration" value="10" required> 10</label>
                    <label><input type="radio" name="duration" value="20"> 20</label>
                    <label><input type="radio" name="duration" value="30"> 30</label>
                    <label><input type="radio" name="duration" value="40"> 40</label>
                    <label><input type="radio" name="duration" value="50"> 50</label>
                    <label><input type="radio" name="duration" value="60"> 60</label>
                    <label><input type="radio" name="duration" value="custom"> Custom</label>

                    <input type="number" step="0.01" id="custom-duration" name="custom_duration"
                        placeholder="Enter duration (minutes)"
                        style="display:none; border: none;  border-bottom: 1px solid #000; width: 100px;" required>
                </div>

                <script>
                    // Listen for changes on the duration radio buttons
                    document.querySelectorAll('input[name="duration"]').forEach((radio) => {
                        radio.addEventListener('change', function () {
                            // Show the custom duration input only if 'Custom' is selected
                            const customInput = document.getElementById('custom-duration');
                            if (this.value === 'custom') {
                                customInput.style.display = 'inline-block';
                                customInput.required = true; // Make it required when shown
                            } else {
                                customInput.style.display = 'none';
                                customInput.value = ''; // Clear custom input value
                                customInput.required = false; // Remove required attribute
                            }
                        });
                    });
                </script>
                <br><br>
                <label for="category">Select Category:</label>
                <select name="category" id="category" required>
                    <option value="Running">Running</option>
                    <option value="Jogging">Jogging</option>
                    <option value="Swimming">Swimming</option>
                    <option value="Cycling">Cycling</option>
                    <option value="Strength Training">Strength Training</option>
                    <option value="Yoga">Yoga</option>
                    <option value="Walking">Walking</option>
                    <option value="Dancing">Dancing</option>
                    <option value="Pilates">Pilates</option>
                </select><br><br>
                <button type="submit" name="add_exercise" class="action-button add-button">Add Exercise Record</button>
            </form>
        </div>

        <div style="display:flex; justify-content: space-between; align-items: center;">
            <h2>Your Exercise Routines</h2>

            <div class="button-container">
                <form method="POST" action="">
                    <button type="submit" class="search-button reset-button" name="reset">Reset</button>
                </form>
                <button class="search-button" onclick="openSearchModal()">
                    <img src="search-icon.png" alt="Search"> Search
                </button>
            </div>

            <div id="searchModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeSearchModal()">&times;</span>
                    <h2>Search Exercise Records</h2>

                    <form method="POST" action="">
                        <label for="searchType">Search by:</label>
                        <select name="searchType" id="searchType" required onchange="toggleSearchInput()">
                            <option value="date">Date</option>
                            <option value="last_week">Last Week</option>
                            <option value="last_month">Last Month</option>
                            <option value="Running">Running</option>
                            <option value="Jogging">Jogging</option>
                            <option value="Swimming">Swimming</option>
                            <option value="Cycling">Cycling</option>
                            <option value="Strength Training">Strength Training</option>
                            <option value="Yoga">Yoga</option>
                            <option value="Walking">Walking</option>
                            <option value="Dancing">Dancing</option>
                            <option value="Pilates">Pilates</option>
                        </select>

                        <input type="date" name="searchValue" id="searchValue" placeholder="Enter Date"
                            value="<?php echo htmlspecialchars($searchValue); ?>">
                        <input type="submit" class="blue-search-button" name="search" value="Search">
                    </form>
                </div>
            </div>
        </div>

        <table>
            <tr>
                <th>Date</th>
                <th>Duration (minutes)</th>
                <th>Exercise</th>
                <th>Action</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['recordDate']; ?></td>
                    <td><?php echo $row['duration']; ?></td>
                    <td><?php echo $row['category']; ?></td>
                    <td>
                        <button class="action-button update-button" onclick="openUpdateModal('<?php echo $row['id']; ?>', 
                        '<?php echo $row['category']; ?>', '<?php echo $row['duration']; ?>', 
                        '<?php echo $row['recordDate']; ?>')">Update</button>
                        <a href="exerciseRoutine.php?delete=<?php echo $row['id']; ?>" class="action-button delete-button"
                            onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUpdateModal()">&times;</span>
            <h2>Update Exercise Routine</h2>
            <form method="POST" action="">
                <input type="hidden" name="id" id="updateId">
                <input type="date" style="border-radius: 10px; padding: 13px;
            margin: 10px 0; border: 1px solid #ddd;" name="recordDate" id="updateDate" required><br>
                <input type="number" style="border-radius: 10px; padding: 13px;
            margin: 10px 0; border: 1px solid #ddd;" name="duration" id="updateDuration" required placeholder="Duration (minutes)"><br>

                <label for="updateCategory">Select Category:</label>
                <select style="border-radius: 10px; padding: 13px;
            margin: 10px 0; border: 1px solid #ddd;" name="category" id="updateCategory" required>
                    <option value="Running">Running</option>
                    <option value="Jogging">Jogging</option>
                    <option value="Swimming">Swimming</option>
                    <option value="Cycling">Cycling</option>
                    <option value="Strength Training">Strength Training</option>
                    <option value="Yoga">Yoga</option>
                    <option value="Walking">Walking</option>
                    <option value="Dancing">Dancing</option>
                    <option value="Pilates">Pilates</option>
                </select><br>
                <button type="submit" name="update_exercise" class="action-button update-button">Update</button>
                <button type="button" class="action-button delete-button" onclick="closeUpdateModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div id="noRecordModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="no-result">
                <span class="close" onclick="closeNoRecordModal()">&times;</span>
                <h2>No Record Found</h2>
                <p>There are no records matching your search criteria.</p>
            </div>
        </div>
    </div>

    <script>

        document.addEventListener("DOMContentLoaded", function () {
            const recordDateInput = document.getElementById('recordDate');

            // Get today's date formatted as YYYY-MM-DD
            const today = new Date();
            const formattedDate = today.toISOString().slice(0, 10);

            // Set the default value of the date input to today's date
            recordDateInput.value = formattedDate;
        });


        function openUpdateModal(id, category, duration, date) {
            document.getElementById('updateId').value = id;
            document.getElementById('updateCategory').value = category;
            document.getElementById('updateDuration').value = duration;
            document.getElementById('updateDate').value = date;
            document.getElementById('updateModal').style.display = 'block';
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        function openSearchModal() {
            document.getElementById('searchModal').style.display = 'block';
        }

        function closeSearchModal() {
            document.getElementById('searchModal').style.display = 'none';
        }

        window.onclick = function (event) {
            const updateModal = document.getElementById('updateModal');
            const searchModal = document.getElementById('searchModal');
            if (event.target === updateModal) {
                closeUpdateModal();
            } else if (event.target === searchModal) {
                closeSearchModal();
            }
        }

        function openNoRecordModal() {
            document.getElementById('noRecordModal').style.display = 'block';
            document.getElementById('noRecordModal').style.zIndex = '100';
        }

        function closeNoRecordModal() {
            document.getElementById('noRecordModal').style.display = 'none';
            window.location.href = "exerciseRoutine.php";//Show all records
        }

        <?php if (mysqli_num_rows($result) == 0 && isset($_POST['search'])) { ?>
            openNoRecordModal(); // Open modal if no records found
        <?php } ?>

        // Show message
        function showMessage(type, message) {
            const messageContainer = document.createElement('div');
            messageContainer.className = `message ${type}`;
            messageContainer.textContent = message;

            document.getElementById('message-container').appendChild(messageContainer);

            // Show the message
            messageContainer.style.display = 'block';
            setTimeout(() => {
                messageContainer.style.opacity = 1; // Fade in
            }, 10);

            // Hide after 3 seconds
            setTimeout(() => {
                messageContainer.style.opacity = 0; // Fade out
                setTimeout(() => {
                    messageContainer.remove(); // Remove from DOM after fade out
                }, 500); // Wait for fade out to finish
            }, 3000); // Show for 3 seconds
        }

        /*hide or show the input box based on the selected search type*/
        function toggleSearchInput() {
            const searchType = document.getElementById('searchType').value;
            const searchValueInput = document.getElementById('searchValue');

            if (searchType === 'date') {
                searchValueInput.type = 'date';
                searchValueInput.style.display = 'block';
                searchValueInput.setAttribute('required', 'required');
            } else {
                searchValueInput.style.display = 'none';
                searchValueInput.removeAttribute('required');
            }
        }
        window.onload = function () {
            toggleSearchInput();
            <?php if ($error) { ?>
                showMessage('error', '<?php echo addslashes($error); ?>');
            <?php } elseif ($success) { ?>
                showMessage('success', '<?php echo addslashes($success); ?>');
            <?php } ?>
        };
    </script>
</body>

</html>