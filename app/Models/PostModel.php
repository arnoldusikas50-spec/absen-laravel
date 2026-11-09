<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/PostModel.php
 * Tabel: post
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class PostModel extends Model
{
    protected $table = 'post';

    protected $primaryKey = 'id_post';

    protected $fillable = [
    ];

    public $timestamps = false;

    // TODO: tabel post belum ada migrasinya
    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel master_panitia belum ada migrasinya
    // TODO: tabel post_comments belum ada migrasinya
    public function getPostUser($id_user)
    {
        $builder = DB::table('post as a');
        $builder->selectRaw('a.*, b.nama_guru, b.foto, d.nama_panitia, d.foto as foto_panitia, (SELECT COUNT(post_comments.id_comment) FROM post_comments WHERE a.id_post = post_comments.id_post) AS jml');
        $builder->leftJoin('master_guru as b', 'a.dari', '=', 'b.id_guru');
        $builder->leftJoin('master_panitia as d', 'a.dari', '=', 'd.id_panitia');
        if ($id_user != 0) {
            $builder->where('a.dari', $id_user);
        }
        $builder->orderBy('a.updated', 'desc');

        return $builder->get()->all();
    }

    // TODO: tabel post belum ada migrasinya
    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel post_comments belum ada migrasinya
    public function getPostForUser($kepada, $kelas = null, $limit = null, $offset = null)
    {
        $builder = DB::table('post as a');
        $builder->selectRaw('a.*, b.nama_guru, b.foto, (SELECT COUNT(post_comments.id_comment) FROM post_comments WHERE a.id_post = post_comments.id_post) AS jml');
        $builder->leftJoin('master_guru as b', 'a.dari', '=', 'b.id_guru');

        // If kepada is '0', show all posts (for admin)
        // Otherwise, filter by kepada and kelas
        if ($kepada !== '0' && $kepada !== 0 && $kepada !== null) {
            // Remove quotes from kepada if present (e.g., "'siswa'" -> "siswa")
            $kepadaClean = trim($kepada, "'\"");

            // Build proper LIKE conditions for JSON array search.
            // CI4 escapeLikeString() dipakai di sini; Laravel tidak punya
            // padanannya, jadi karakter LIKE (backslash, %, _) di-escape
            // manual dengan backslash (escape default MySQL).
            $escapeLike = static fn ($value) => str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $value);
            $whereClause = '(a.kepada LIKE ?)';
            $bindings = ['%'.$escapeLike($kepadaClean).'%'];

            // Add kelas condition if kelas is valid (not 0 or null)
            if ($kelas && $kelas != 0) {
                $whereClause .= ' OR (a.kepada LIKE ?)';
                $bindings[] = '%'.$escapeLike($kelas).'%';
            }

            $builder->whereRaw($whereClause, $bindings);
        }
        // If kepada is '0' (admin), no filter is applied - show all posts

        $builder->orderBy('a.updated', 'desc');
        if ($limit != null) {
            $builder->limit($limit)->offset((int) $offset);
        }

        return $builder->get()->all();
    }

    // TODO: tabel post_comments belum ada migrasinya
    public function getIdComments($id_post)
    {
        $builder = DB::table('post_comments');
        $builder->select('id_comment');
        $builder->where('id_post', $id_post);

        return $builder->get()->all();
    }

    // TODO: tabel post_reply belum ada migrasinya
    public function getIdReplies($id_comment)
    {
        $builder = DB::table('post_reply');
        $builder->select('id_reply');
        if (is_array($id_comment)) {
            $builder->whereIn('id_comment', $id_comment);
        } else {
            $builder->where('id_comment', $id_comment);
        }

        return $builder->get()->all();
    }
}
