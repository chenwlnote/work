<?php
/**
 * 应用元信息
 * User: Dave
 * Date: 2017/4/17
 * Time: 14:29
 */

namespace App\Services;


use App\Models\HeyBoAppMeta;

class AppMetaService extends BaseService
{

    /**
     * 通过应用元信息获取APP_ID
     * @param array $param
     * @return array
     */
    public static function getAppIdByMeta(array $param = [])
    {
        $model = new HeyBoAppMeta();

        $i = 0;
        foreach ((array)$param as $key=>$value) {
            try {
                if (!empty($key)) {
                    if ($i == 0) {
                        $i++;
                        $model = $model->where('meta_key', $key)->where('meta_value', $value);
                    }else{
                        $i++;
                        $model = $model->orWhere(function ($query) use($key,$value){
                            $query->where('meta_key', $key)->where('meta_value',$value);
                        });
                    }
                }
            }catch (\Exception $e) {
                continue;
            }
        }
        $model = $model->select('app_id');
        $record = $model->get()->toArray();
        if ($record) {
            $result = array_column($record,'app_id');
            $result = array_unique($result);
        }

        return $result??[];

    }

    /**
     * 插入一条数据
     * @param array $data
     */
    public static function createAppMeta(array $data = [])
    {
        $insertData = [
            'app_id'=>$data['app_id'],
            'meta_key'=>$data['meta_key'],
            'meta_value'=>$data['meta_value']
        ];

        $result = HeyBoAppMeta::create($insertData);
        return $result;
    }

    /**
     * 批量插入数据
     * @param array $data
     * @return array
     */
    public static function insertAppMetas(array $data = [])
    {
        $result = HeyBoAppMeta::insert($data);
        return $result??[];
    }
}