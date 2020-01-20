<?php


namespace app\api\controller;

use cmf\controller\RestBaseController;
use cmf\lib\Upload;
use think\Validate;

class UploadController extends RestBaseController
{

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function one()
    {
        if ($this->request->isPost()) {
            $uploader = new Upload();
            $res = $uploader->uploadVideo();
            if ($res === false) {
                $result = [
                    'error' => 1,
                    'message' => $uploader->getError()
                ];
            } else {
                $result = [
                    'error' => 0,
                    'url' => str_replace('\\', '/', '/upload/' . $res["filepath"])
                ];
            }
            return json($result);
        }
    }

    public function upload()
    {
        $file = $this->request->file('file');
        $upload_path = str_replace('\\', '/', CMF_ROOT . 'public/uploads');
        $save_path = '/uploads/';
//        $new_upload = new Upload();
//        $res = $new_upload->upload();
        $info = $file->move($upload_path);
        if ($info) {
            $result = [
                'error' => 0,
                'url' => str_replace('\\', '/', $save_path . $info->getSaveName())
            ];
        } else {
            $result = [
                'error' => 1,
                'message' => $file->getError()
            ];
        }
        return json($result);
    }

    public function photoUpload()
    {
        $file = $this->request->file('file');

        $info     = $file->validate([
//            'size' => 15678,
            'ext' => 'jpg,png,gif'
        ]);
        $upload_path = str_replace('\\', '/', CMF_ROOT . 'public/uploads');
        $save_path = '/uploads/';
        // 移动到框架应用根目录/public/upload/contract/ 目录下
        $info = $info->move($upload_path);
        if ($info) {
            return json([
                'errno' => 0,
                'data' => [str_replace('\\', '/', $save_path . $info->getSaveName())],
            ]);
        } else {
            // 上传失败获取错误信息
            return json([
                'errno' => 1,
            ]);
        }
    }
}