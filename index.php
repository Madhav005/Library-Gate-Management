<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEC-Library Gate Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .scanning-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 400px;
        }

        .scanning-container h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .message {
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }

        .error-message {
            color: red;
        }

        .success-message {
            color: green;
        }

        .sidebar-right, .sidebar-left {
            position: absolute;
            top: 10%;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            max-height: 80%;
            display: flex;
            flex-direction: column;
        }

        .sidebar-right {
            right: 2%;
        }

        .sidebar-left {
            left: 2%;
        }

        .sidebar h3 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .table-container {
            flex-grow: 1;
            overflow-y: auto;
            max-height: calc(100% - 50px);
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th, .user-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        .user-table th {
            background-color: #f4f4f4;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .user-count {
            text-align: center;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }

        .logo {
            display: block;
            margin: 0 auto 10px; /* Center the logo and add spacing below */
            width: 400px; /* Adjust as needed */
            height: auto; /* Maintain aspect ratio */
        }

        .library-title {
            text-align: center;
            font-weight: bold;
            font-size: 20px;
            color: red;
            margin-bottom: 10px; /* Add spacing below the title */
        }

        .manual-entry {
            margin-top: 20px;
            text-align: center;
        }

        .buttons-container {
            margin-top: 20px;
            text-align: center;
        }

        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            max-width: 200px;
            margin: 5px;
        }

        .btn:hover {
            background-color: #45a049;
        }
    </style>
    <script>
        function submitForm() {
            var regNumber = document.getElementById('registration_number').value;
            var messageElement = document.getElementById('message');

            if (regNumber === "") {
                messageElement.innerHTML = "Registration number cannot be empty.";
                messageElement.classList.add('error-message');
                messageElement.classList.remove('success-message');
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "process.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    messageElement.innerHTML = response.message;
                    if (response.status === 'success') {
                        messageElement.classList.add('success-message');
                        messageElement.classList.remove('error-message');
                        fetchCheckedInUsers();
                        fetchCheckedOutUsers();
                    } else {
                        messageElement.classList.add('error-message');
                        messageElement.classList.remove('success-message');
                    }
                }
            };
            xhr.send("registration_number=" + regNumber);

            // Clear the input field after submission
            document.getElementById('registration_number').value = '';
        }

        function fetchCheckedInUsers() {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "get_checked_in_users.php", true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var users = JSON.parse(xhr.responseText);
                    var usersList = document.getElementById('users-list-in');
                    var userCount = document.getElementById('users-count-in'); // Element to display user count
                    usersList.innerHTML = '';
                    userCount.innerHTML = '';

                    if (users.length > 0) {
                        var table = "<table class='user-table'>";
                        table += "<tr><th>Name</th><th>Department</th><th>In Time</th></tr>";
                        users.forEach(function(user) {
                            table += "<tr><td>" + user.name + "</td><td>" + user.dept + "</td><td>" + user.in_time + "</td></tr>";
                        });
                        table += "</table>";
                        usersList.innerHTML = table;

                        // Display the number of checked-in users
                        userCount.innerHTML = "Total Checked-in Users: " + users.length;

                        // Scroll the user list to the bottom
                        scrollToBottom();
                    } else {
                        usersList.innerHTML = "No users are currently checked in.";
                        userCount.innerHTML = "Total Checked-in Users: 0";
                    }
                }
            };
            xhr.send();
        }

        function fetchCheckedOutUsers() {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "get_checked_out_users.php", true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var users = JSON.parse(xhr.responseText);
                    var usersList = document.getElementById('users-list-out');
                    var userCount = document.getElementById('users-count-out'); // Corrected element
                    usersList.innerHTML = '';
                    userCount.innerHTML = '';

                    if (users.length > 0) {
                        var table = "<table class='user-table'>";
                        table += "<tr><th>Name</th><th>Department</th><th>Out Time</th></tr>";
                        users.forEach(function(user) {
                            table += "<tr><td>" + user.name + "</td><td>" + user.dept + "</td><td>" + user.out_time + "</td></tr>";
                        });
                        table += "</table>";
                        usersList.innerHTML = table;

                        // Display the number of checked-out users
                        userCount.innerHTML = "Total Checked-out Users: " + users.length;

                        // Scroll the user list to the bottom
                        scrollToBottom();
                    } else {
                        usersList.innerHTML = "No users are currently checked out.";
                        userCount.innerHTML = "Total Checked-out Users: 0";
                    }
                }
            };
            xhr.send();
        }

        function scrollToBottom() {
            var usersList = document.getElementById('users-list');
            usersList.scrollTop = usersList.scrollHeight;
        }

        window.onload = function () {
            const inputField = document.getElementById("registration_number");

            // Focus input field and fetch checked-in users on page load
            inputField.focus();
            fetchCheckedInUsers();
            fetchCheckedOutUsers();

            // Keep the input field focused regardless of clicks
            document.body.addEventListener("mousedown", function (event) {
                event.preventDefault(); // Prevent other elements from taking focus
                inputField.focus();
            });

            // Trigger form submission on Enter key press
            inputField.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    submitForm();
                }
            });
        };
    </script>
</head>
<body>
<div class="scanning-container">
    <img src="http://training.saveetha.in/pluginfile.php/1/core_admin/logo/0x150/1623542614/logo_1.png" 
         alt="Library Logo" class="logo">
    <h2 class="library-title">SEC CENTRAL LIBRARY</h2>
    <h1>Library Gate Management</h1>
    
    <div id="message" class="message"></div>
    
    <!-- Manual Entry Form -->
    <div id="manual-entry" class="manual-entry">
        <div class="form-group">
            <label for="registration_number">Registration Number:</label>
            <input type="text" id="registration_number" name="registration_number" required>
        </div>
        <button type="button" class="btn" onclick="submitForm()">Submit</button>
    </div>
</div>
<div class="sidebar-left">
    <h3>Checked-out Users</h3>
    <div class="table-container" id="users-list-out"></div>
    <div id="users-count-out" class="user-count"></div> <!-- Corrected the ID -->
</div>
<div class="sidebar-right">
    <h3>Checked-in Users</h3>
    <div class="table-container" id="users-list-in"></div>
    <div id="users-count-in" class="user-count"></div> <!-- Corrected the ID -->
</div>
</body>
</html>
