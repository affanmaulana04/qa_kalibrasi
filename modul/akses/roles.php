<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../index.php'); exit; }
require_once __DIR__.'/../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
if (!qaRbacIsAdmin($konek)) { http_response_code(403); exit('Hanya Admin QA yang dapat mengatur Custom Role.'); }
function rh($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
$ready=qaRbacReady($konek);
if (empty($_SESSION['qa_role_csrf'])) $_SESSION['qa_role_csrf']=bin2hex(random_bytes(20));
$csrf=$_SESSION['qa_role_csrf'];
$error='';$notice='';
$catalog=qaRbacCatalog();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrf,(string)($_POST['csrf']??''))) { http_response_code(403);exit('Token keamanan tidak valid.'); }
    if (!$ready) { $error='Tabel role belum tersedia. Import SQL migrasi terlebih dahulu.'; }
    else {
        $action=(string)($_POST['action']??'');
        $id=(int)($_POST['role_id']??0);
        if ($action==='save') {
            $name=trim((string)($_POST['nama_role']??''));
            $desc=trim((string)($_POST['deskripsi']??''));
            $selected=$_POST['permissions']??[];
            if (!is_array($selected)) $selected=[];
            $selected=array_values(array_intersect(array_keys($catalog),array_map('strval',$selected)));
            if ($name==='' || strlen($name)>70 || strlen($desc)>255) $error='Nama role wajib diisi (maks. 70 karakter).';
            elseif (!$selected) $error='Pilih minimal satu izin akses untuk role.';
            else {
                if ($id>0) {
                    $s=$konek->prepare('UPDATE qa_roles SET nama_role=?,deskripsi=?,updated_at=NOW() WHERE id=?');
                    if ($s) {$s->bind_param('ssi',$name,$desc,$id);$ok=$s->execute();$s->close();}else{$ok=false;}
                } else {
                    $s=$konek->prepare('INSERT INTO qa_roles (nama_role,deskripsi,aktif,created_at) VALUES (?,?,1,NOW())');
                    if ($s) {$s->bind_param('ss',$name,$desc);$ok=$s->execute();$id=(int)$konek->insert_id;$s->close();}else{$ok=false;}
                }
                if (!$ok) $error='Gagal menyimpan role: '.mysqli_error($konek);
                else {
                    $ok=mysqli_query($konek,'DELETE FROM qa_role_permissions WHERE role_id='.(int)$id);
                    $s=$konek->prepare('INSERT INTO qa_role_permissions (role_id,permission_key) VALUES (?,?)');
                    if (!$s) $ok=false;
                    else {
                        foreach($selected as $permission){
                            $s->bind_param('is',$id,$permission);
                            if(!$s->execute()) {$ok=false;break;}
                        }
                        $s->close();
                    }
                    if ($ok) {header('Location: roles.php?success=save');exit;}
                    $error='Role tersimpan tetapi hak akses belum berhasil diperbarui. Coba simpan ulang.';
                }
            }
        } elseif ($action==='delete' && $id>0) {
            $used=mysqli_query($konek,'SELECT uid FROM qa_user_roles WHERE role_id='.$id.' LIMIT 1');
            if ($used && mysqli_num_rows($used)>0) $error='Role masih dipakai user. Pindahkan user ke role lain sebelum menghapus.';
            else {
                if (mysqli_query($konek,'DELETE FROM qa_roles WHERE id='.$id)) {
                    mysqli_query($konek,'DELETE FROM qa_role_permissions WHERE role_id='.$id);
                    header('Location: roles.php?success=delete');exit;
                }
                $error='Gagal menghapus role.';
            }
        }
    }
}
if (isset($_GET['success'])) $notice=$_GET['success']==='delete'?'Role berhasil dihapus.':'Role berhasil disimpan.';
$roles=[];$permissionsByRole=[];$counts=[];
if ($ready) {
    $q=mysqli_query($konek,'SELECT r.id,r.nama_role,r.deskripsi,r.aktif,COUNT(a.uid) AS jumlah_user FROM qa_roles r LEFT JOIN qa_user_roles a ON a.role_id=r.id GROUP BY r.id,r.nama_role,r.deskripsi,r.aktif ORDER BY r.nama_role');
    if ($q) while($r=mysqli_fetch_assoc($q)) $roles[]=$r;
    $q=mysqli_query($konek,'SELECT role_id,permission_key FROM qa_role_permissions');
    if ($q) while($r=mysqli_fetch_assoc($q)) $permissionsByRole[(int)$r['role_id']][$r['permission_key']]=true;
}
$editing=null;$editId=max(0,(int)($_GET['edit']??0));
foreach($roles as $r) if ((int)$r['id']===$editId){$editing=$r;break;}
$groups=[];
foreach($catalog as $key=>$cfg) { if ($key==='pengguna') continue; $groups[$cfg[0]][$key]=$cfg[1]; }
?><!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Custom Role & Hak Akses</title>
<link rel="icon" type="image/png" href="../../images/favicon-toto-qa.png?v=1">
<link rel="stylesheet" href="../../css/bootstrap.min.css"><link rel="stylesheet" href="../../font-awesome-4.3.0/css/font-awesome.min.css">
<style>body{background:#f4f6f9;padding-top:25px}.panel{border-radius:8px}.permission{display:block;margin:9px 0;font-weight:normal}.permission small{color:#666}.section-title{border-bottom:1px solid #ddd;padding-bottom:8px;margin:20px 0 10px}.table td{vertical-align:middle!important}.header{margin-bottom:20px}.muted{color:#777}</style></head><body>
<div class="container-fluid" style="max-width:1250px">
<div class="header"><a class="btn btn-default" href="../../media.php"><i class="fa fa-arrow-left"></i> Menu Utama</a> <a class="btn btn-default" href="../tabel/pemakai.php">Kelola User</a></div>
<h2 style="color:#1e3f66">Custom Role &amp; Hak Akses</h2>
<?php if (!$ready): ?><div class="alert alert-danger"><b>Tabel hak akses belum terpasang.</b> Import <code>database/migrations/20261020_qa_custom_roles.sql</code> lewat phpMyAdmin sebelum membuat role.</div><?php endif; ?>
<?php if ($notice): ?><div class="alert alert-success"><?=rh($notice)?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?=rh($error)?></div><?php endif; ?>
<div class="row"><div class="col-md-5">
<div class="panel panel-default"><div class="panel-heading"><b>Daftar Role</b></div><div class="panel-body">
<a class="btn btn-success btn-sm" href="roles.php"><i class="fa fa-plus"></i> Role Baru</a>
<div class="table-responsive" style="margin-top:15px"><table class="table table-bordered table-striped"><thead><tr><th>Nama Role</th><th>User</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($roles as $r): ?><tr><td><b><?=rh($r['nama_role'])?></b><div class="small muted"><?=rh($r['deskripsi'])?></div></td><td class="text-center"><?=(int)$r['jumlah_user']?></td><td style="white-space:nowrap"><a class="btn btn-xs btn-primary" href="?edit=<?=(int)$r['id']?>">Edit</a>
<?php if ((int)$r['jumlah_user']===0): ?><form method="post" style="display:inline" onsubmit="return confirm('Hapus role ini?')"><input type="hidden" name="csrf" value="<?=rh($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="role_id" value="<?=(int)$r['id']?>"><button class="btn btn-xs btn-danger" type="submit">Hapus</button></form><?php endif; ?>
</td></tr><?php endforeach; ?><?php if(!$roles): ?><tr><td colspan="3" class="text-center">Belum ada role custom.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
<div class="col-md-7"><div class="panel panel-default"><div class="panel-heading"><b><?=$editing?'Edit Role: '.rh($editing['nama_role']):'Buat Role Baru'?></b></div><div class="panel-body"><form method="post">
<input type="hidden" name="csrf" value="<?=rh($csrf)?>"><input type="hidden" name="action" value="save"><input type="hidden" name="role_id" value="<?=(int)($editing['id']??0)?>">
<div class="form-group"><label>Nama Role *</label><input type="text" name="nama_role" maxlength="70" class="form-control" required value="<?=rh($editing['nama_role']??'')?>" placeholder="Contoh: Management, Kalibrator, Pemeriksa"></div>
<div class="form-group"><label>Deskripsi</label><input type="text" name="deskripsi" maxlength="255" class="form-control" value="<?=rh($editing['deskripsi']??'')?>"></div>
<b>Pilih halaman & hak aksi:</b>
<?php foreach($groups as $group=>$items): ?><h4 class="section-title"><?=rh($group)?></h4>
<?php foreach($items as $key=>$desc): ?><label class="permission"><input type="checkbox" name="permissions[]" value="<?=rh($key)?>" <?=!empty($permissionsByRole[$editId][$key])?'checked':''?>> <b><?=rh(ucwords(str_replace('_',' ',$key)))?></b> <small>— <?=rh($desc)?></small></label><?php endforeach; ?><?php endforeach; ?>
<div style="margin-top:22px"><button type="submit" class="btn btn-primary" <?=$ready?'':'disabled'?>> <i class="fa fa-save"></i> Simpan Role</button> <a class="btn btn-default" href="roles.php">Batal</a></div>
</form></div></div></div></div>
</div></body></html>
