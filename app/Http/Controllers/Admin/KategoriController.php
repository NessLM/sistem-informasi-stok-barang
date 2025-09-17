<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kategori extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Gudang_model');
        $this->load->model('Kategori_model');
    }

    public function tambah()
    {
        // ambil semua gudang
        $data['gudang'] = $this->Gudang_model->get_all();

        // load view form tambah kategori
        $this->load->view('kategori/tambah', $data);
    }

    public function simpan()
    {
        $nama_kategori = $this->input->post('nama_kategori');
        $id_gudang     = $this->input->post('id_gudang');

        $data = [
            'nama_kategori' => $nama_kategori,
            'id_gudang'     => $id_gudang
        ];

        $this->Kategori_model->insert($data);

        redirect('kategori');
    }
}
