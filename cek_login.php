<?php
include "inc/inc_koneksi.php";

session_start();
$_SESSION['jamlogin'] = gmdate("H:i:s", time() + 60 * 60 * 7);

$userid = $_POST['userid'];
$pass   = $_POST['password'];

$pc_name = gethostbyaddr($_SERVER['REMOTE_ADDR']);
$_SESSION['nama_pc'] = $pc_name;

$login  = mysqli_query($konek, "SELECT * FROM user WHERE uid='$userid'");
$ketemu = mysqli_num_rows($login);

if ($ketemu > 0) {
    $r = mysqli_fetch_array($login);
    $userid_tbl   = $r['uid'];
    $username_tbl = $r['uname'];
    $pword_tbl    = $r['pword'];
    $admin        = $r['admin'];
    $role         = $r['role'];
    $block        = $r['block'];
    $sapaan       = $r['sapaan'];
    $seksi_user   = $r['seksi'];
    $email_user   = $r['email_pembuat'];
    
    // Cookie management
    if (!empty($_POST["remember"])) {
        setcookie("userid", $_POST["userid"], time() + (60 * 60 * 24 * 3));
        setcookie("password", $_POST["password"], time() + (60 * 60 * 24 * 3));
    } else {
        setcookie("userid", "");
        setcookie("password", "");
    }

    // Set default LDAP N untuk QA System (Bisa diubah jadi Y jika pakai LDAP lagi)
    $ldap = 'N'; 

    if ($block == 'N') {
        if ($ldap == 'Y') {
            // LOGIKA LDAP (Disimpan sesuai aslinya jika suatu saat dipakai lagi)
            include 'ldap.php';
            $ldap_conn = new ldap();
            $ldap_conn->s_Host('10.81.250.12');
            $ldap_conn->s_Domain('toto.local');
            $ldap_conn->s_LdapSecure(false);

            $namauser = $ldap_conn->s_User($userid);
            $pwduser  = $ldap_conn->s_Pass($pass);
            
            if ($ldap_conn->g_User() && $ldap_conn->g_Pass()) {
                if ($ldap_conn->LdapConn()) {
                    if ($ldap_conn->LdapBind()) {
                        $name = $ldap_conn->getAttribute('cn');
                        if ($name != '') {
                            $_SESSION['userid']         = $userid;
                            $_SESSION['username']       = $name;
                            $_SESSION['role']           = $role;
                            $_SESSION['login']          = 1;
                            $_SESSION['admin']          = $admin;
                            $_SESSION['sapaan']         = $sapaan;
                            $_SESSION['seksi_user']     = $seksi_user;
                            $_SESSION['email_user']     = $email_user;
                            $_SESSION['namaemail_user'] = $username_tbl;

                            mysqli_query($konek, "UPDATE user SET uname='$name', online='Y', pc_login='$pc_name' WHERE uid='$userid'");
                            header('location:media.php');
                        }
                    } else {
                        echo "<center><br><br><br><br><br><br>Invalid Password<br><br>";
                        echo "<div> <a href='index.php'><img src='images/lock.png' height=50 width=50></a></div>";
                        echo "<br><input type=button value='Return' onclick=location.href='index.php'></a></center>";
                        return false;
                    }
                } else {
                    echo "<center><br><br><br><br><br><br>LDAP Server Not Available<br><br>";
                    echo "<div> <a href='index.php'><img src='images/folder.png' height=50 width=50></a></div>";
                    echo "<br><input type=button value='Return' onclick=location.href='index.php'></a></center>";
                    return false;
                }
            } else {
                echo "<font color='red'>Warning : Input Username and Password</font>";
            }
        } else {
            // LOGIKA NON-LDAP (SISTEM STANDAR)
            if ($pword_tbl == md5($pass)) {
                $_SESSION['userid']         = $userid_tbl;
                $_SESSION['username']       = $username_tbl;
                $_SESSION['role']           = $role;
                $_SESSION['login']          = 1;
                $_SESSION['admin']          = $admin;
                $_SESSION['sapaan']         = $sapaan;
                $_SESSION['seksi_user']     = $seksi_user;
                $_SESSION['email_user']     = $email_user;
                $_SESSION['namaemail_user'] = $username_tbl;
                
                mysqli_query($konek, "UPDATE user SET online='Y', pc_login='$pc_name' WHERE uid='$userid_tbl'");
                
                header('location:media.php');
            } else {
                echo "<center><br><br><br><br><br><br>Invalid Password<br><br>";
                echo "<div> <a href='index.php'><img src='images/lock.png' height=50 width=50></a></div>";
                echo "<br><input type=button value='Return' onclick=location.href='index.php'></a></center>";
                return false;
            }
        }
    } else {
        echo "<center><br><br><br><br><br><br>User id <b>$userid</b> has been blocked<br><br>";
        echo "<div> <a href='index.php'><img src='images/user.png' height=50 width=50></a></div>";
        echo "<br><input type=button value='Return' onclick=location.href='index.php'></a></center>";
        return false;
    }
} else {
    echo "<center><br><br><br><br><br><br>User id <b>$userid</b> hasn't been registered yet<br><br>";
    echo "<div> <a href='index.php'><img src='images/user.png' height=50 width=50></a></div>";
    echo "<br><input type=button class='button buttonblue mediumbtn' value='Return' onclick=location.href='index.php'></a></center>";
    return false;
}
?>