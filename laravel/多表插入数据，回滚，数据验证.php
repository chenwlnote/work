<?php
/**
 * 应用服务
 * User: Dave
 * Date: 2017/4/17
 * Time: 14:28
 */

namespace App\Services;


use App\Models\HeyBoApps;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppsService extends BaseService
{

    const ErrorCodePrefix = 11000;


    /**
     * 通过类别获取应用列表
     * @param int $categoryId
     * @return array
     */
    public static function getAppListByCategory(int $categoryId)
    {
        $record = HeyBoApps::where('category_pid',$categoryId)->get();
        $result = static::getAppMetaInfo($record);
        return $result??[];
    }

    public static function searchApp(array $param = [])
    {
        $keyword = $param['keyword']??'';
        $model = new HeyBoApps();
        if (!empty($keyword)) {
            $model = $model->where('app_name','like','%'.$keyword.'%');
            unset($param['keyword']);
        }
        if (!empty($param)) {
            $appid = AppMetaService::getAppIdByMeta($param);
            $model = $model->whereIn('app_id',$appid);
        }
        $record = $model->get();
        $result = static::getAppMetaInfo($record);
        return $result;
    }

    /**
     * 获取应用元信息
     * @param $record
     * @return array
     */
    public static function getAppMetaInfo($record)
    {
        $result = [];
        foreach ($record as $row) {
            $appInfo = [];
            $appInfo['app_name'] = $row['app_name'];
            $appInfo['app_version'] = $row['app_version'];

            $appInfo = array_merge($appInfo,$row->AppMetas);
            $result[] = $appInfo;
        }
        return $result;
    }

    /**
     * 获取应用列表
     * @return array
     */
    public static function getAppList()
    {

        $record = HeyBoApps::get();

        $result = static::getAppMetaInfo($record);
        return $result??[];
    }

    /**
     * 验证应用信息
     * @param array $data
     * @return bool
     * @throws HeyBoException
     */
    public static function validateAppData(array $data = [])
    {
        $result = Validator::make($data,
            [
                'category_pid'=>'required|max:10',
                'category_name'=>'required|max:50',
                'category_icon'=>'required|max:200',
                'name'=>'required|max:50'
            ],
            [
                'category_pid.required'=>'父类类别id未填写',
                'category_pid.max'=>'父类类别ID长度不能超过10位',
                'category_name.required'=>'类别名称未填写',
                'category_name.max'=>'类别名称不得超过50字',
                'category_icon.required'=>'类别图标未填写',
                'category_icon.max'=>'类别图标长度不得超过200字',
                'name.required'=>'应用名称未填',
                'name.max'=>'应用名称不得超过50字'
            ]
        );
        $errors = $result->errors()->toArray();
        if (count($errors) > 0) {
            throw new HeyBoException($errors,self::ErrorCodePrefix.'401');
        }
        return true;
    }

    /**
     * 新建插件
     * @param array $data
     * @throws HeyBoException
     */
    public static function createPlugin(array $data = [])
    {
        try {


            DB::beginTransaction();

            self::validateAppData($data);
            $categoryPid = $data['category_pid']??0;
            $categoryName = $data['category_name']??'';
            $categoryIcon = $data['category_icon']??'';

            $categoryData = [
                'category_pid' => $categoryPid,
                'category_name' => $categoryName,
                'category_icon' => $categoryIcon
            ];

            $appCategory = AppCategoryService::createAppCategory($categoryData);
            $categoryId = $appCategory['category_id'];
            $insertData = [
                'app_name' => $data['name'],
                'app_version' => $data['version'],
                'category_id' => $categoryId,
                'category_pid'=> $categoryPid
            ];
            unset($data['name'], $data['version'],$data['category_pid'], $data['category_name'], $data['category_icon']);

            $apps = HeyBoApps::create($insertData);
            $appMetaData = [];
            foreach ($data as $key=>$value) {
                $appMetaData[] = ['app_id' => $apps['app_id'], 'meta_key' => $key, 'meta_value' => $value];
            }
            AppMetaService::insertAppMetas($appMetaData);
            DB::commit();
            return $apps;
        }catch (HeyBoException $e){
            DB::rollback();
            throw new HeyBoException($e->getMessage(),self::ErrorCodePrefix.'501');
        }
    }

    /**
     * 通过ID获取应用详细
     * @param int $appId
     */
    public static function getAppDetailByAppId(int $appId)
    {
        $apps = HeyBoApps::where('app_id',$appId)->first();
        $apps['app_meta'] = $apps->AppMetas;
        $apps['category'] = $apps->category;
        return $apps;
    }
}