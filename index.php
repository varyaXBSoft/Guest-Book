<!DOCTYPE html>
<html>
    <head>
        <title>Guest Book</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css"/>
    </head>
    <body>
        <?php
        session_start();
            $servername = "127.0.0.1";
            $user = "root";
            $password = "";
            $port = 3306;
            $dbname = "myTestDB";
            $tableName = "GuestBook";
            $usernameErr = $emailErr = $homepageErr = $messageErr = "";
            $username = $email = $homepage = $message = "";

            $conn = new mysqli($servername, $user, $password);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $database = "CREATE DATABASE IF NOT EXISTS " . $dbname;
            if ($conn->query($database) === TRUE) {
//                echo "Database created successfully";
            } else {
                echo "Error creating database: " . $conn->error;
            }

            $conn = new mysqli($servername, $user, $password, $dbname);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
                                       

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if (empty($_POST["username"])) {
                    $usernameErr = "User Name is required";
                } else {
                    $username = test_input($_POST["username"]);
                    if (!preg_match("/^[a-zA-Z0-9]*$/",$username)) {
                        $usernameErr = "Only letters and digits allowed"; 
                    }
                }
                
                if (empty($_POST["email"])) {
                    $emailErr = "Email is required";
                } else {
                    $email = test_input($_POST["email"]);
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $emailErr = "Invalid email format"; 
                    }
                }
                
                if (empty($_POST["homepage"])) {
                    $homepage = "";
                } else {
                    $homepage = test_input($_POST["homepage"]);
                    if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$homepage)) {
                        $homepageErr = "Invalid URL"; 
                    }
                }
                
                if (empty($_POST["message"])) {
                    $messageErr = "Please, type your message";
                } else {             
                    $message = test_input($_POST["message"]);
                    if($_POST["message"] != strip_tags($_POST["message"])) {
                        $messageErr = "No tags allowed";
                    }                   
                }                              
                        
                //check if table already exist, create if not
                if($conn->query("SELECT * from $tableName")) {
                    if(!$usernameErr && !$emailErr && !$homepageErr && !$messageErr){                       
                        $insertsql = "INSERT INTO GuestBook (username, email, homepage, text)
                        VALUES ('$username', '$email', '$homepage', '$message')";

                        if ($conn->query($insertsql)) {
                            echo "New record created successfully";
                            $usernameErr = $emailErr = $homepageErr = $messageErr = "";
                            $username = $email = $homepage = $message = "";
                        } else {
                            echo "Error: " . $insertsql  . $conn->error;
                        }
                    } else { echo "edit input information"; }
                } else {               
                    $table = "CREATE TABLE $tableName (
                    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
                    username VARCHAR(30) NOT NULL,
                    email VARCHAR(50) NOT NULL,
                    reg_date TIMESTAMP,
                    homepage VARCHAR(50),
                    text VARCHAR(1000) NOT NULL
                    )";

                    if ($conn->query($table)) {
                        echo "Table GuestBook created successfully";
                    } else {
                        echo "Error creating table: " . $conn->error;
                    }      

                    $insertsql = "INSERT INTO $tableName (username, email, homepage, text)
                    VALUES ('$username', '$email', '$homepage', '$message')";

                    if($username && $email && $message){
                        if ($conn->query($insertsql)) {
                            echo "New record created successfully";
                            $usernameErr = $emailErr = $homepageErr = $messageErr = "";
                            $username = $email = $homepage = $message = "";
                        } else {
                            echo "Error: " . $insertsql  . $conn->error;
                        }
                    }
                }
            }
            
            function test_input($data) {
              $data = trim($data);
              $data = stripslashes($data);
              $data = htmlspecialchars($data);
              return $data;
            }
            

        ?>
        <h2>Guest Book</h2>
        <p><span class="error">* required field.</span></p>
        <form method="post" action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>'>
            <label>User Name:</label> <input type="text" name="username" value="<?php echo $username;?>"><span class="error">* <?php echo $usernameErr;?></span><br>
            <label>E-mail:</label> <input type="text" name="email" value="<?php echo $email;?>"><span class="error">* <?php echo $emailErr;?></span><br>
            <label>Homepage:</label> <input type="text" name="homepage" value="<?php echo $homepage;?>"><span class="error"><?php echo $homepageErr;?></span><br>
            <label style="vertical-align: top">Text:</label> <textarea name="message" rows="5" cols="40" value="<?php echo $message;?>"></textarea><span class="error">* <?php echo $messageErr;?></span><br>
            <input type="submit" value="Add message" name="submit" id="submitButton">
        </form>   
        
        <?php
            echo "<h2>Our guests:</h2>";
            echo "<table border='1'>"
                    . "<thead><tr>"
                        . "<th><a href='index.php?sort=name'> User Name </a></th>"
                        . "<th><a href='index.php?sort=email'> Email </a></th>"
                        . "<th><a href='index.php?sort=date'> Post Date </a></th>"
                        . "<th> Homepage </th>"
                        . "<th> Text </th>"
                    . "</tr></thead>"
                    . "<tbody>";
            $results_per_page = 25;
            if (isset($_GET["page"])) { 
                $page  = $_GET["page"]; 
            } else { 
                $page=1;                 
            };
            $start_from = ($page-1) * $results_per_page;
            
            
            $sql =  "SELECT username, email, reg_date, homepage, text FROM GuestBook";
            
            
            
            function getSortOrder() {
                $col = $_GET['sort'];
                $sortOrder = "";
                if(!isset($_GET["page"])){
                    if(isset($_SESSION['sort'.$col]) && $_SESSION['sort'.$col] === 'ASC'){
                        $_SESSION['sort'.$col] = 'DESC';
                        $sortOrder .= "DESC";
                    } else {
                        $_SESSION['sort'.$col] = 'ASC';
                        $sortOrder .= "ASC";
                    }
                } else {
                    $sortOrder .= isset($_SESSION['sort'.$col]) ? $_SESSION['sort'.$col] : "ASC";
                }
                return $sortOrder;
            }    
            
            $sortOrder = getSortOrder();
                
            if ($_GET['sort'] == 'name') {
                $sql .= " ORDER BY username"; 
            } elseif ($_GET['sort'] == 'email') {
                $sql .= " ORDER BY email";
            } elseif ($_GET['sort'] == 'date') {
                $sql .= " ORDER BY reg_date";
            };

            $sql .= " ".$sortOrder." LIMIT $start_from, $results_per_page";
            $result = $conn->query($sql); 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                    echo "</tr>\n";
                }
            } 
            echo "\n</tbody></table>";

            $sql = "SELECT COUNT(ID) AS total FROM GuestBook";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $total_pages = ceil($row["total"] / $results_per_page); // calculate total pages with results

            for ($i=1; $i<=$total_pages; $i++) {  // print links for all pages
                echo "<a href='index.php?page=".$i."&sort=".$_GET['sort']."'";
                if ($i==$page)  echo " class='curPage'";
                echo ">".$i."</a> "; 
            }; 

            $conn->close();
        ?>
    </body>
</html>

