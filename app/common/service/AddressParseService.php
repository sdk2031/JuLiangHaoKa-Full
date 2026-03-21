<?php

namespace app\common\service;

/**
 * 智能地址解析服务
 * 基于 address-smart-parse
 * @version 2.0.5🆕
 */
class AddressParseService
{
    private static $addressList = null;
    
    private static $keyM = ['联系电话', '电话', '联系人手机号码', '联系人', '手机号码', '手机号', '手机', '姓名', '收货人', '收件人'];
    private static $keyA = ['收货地址', '收件地址', '退货地址', '所在地区', '所在地', '联系地址', '送货地址', '详细地址', '地区', '地址'];
    private static $keyD = ['重庆 ', '上海 ', '北京 ', '天津 ', '市辖区', '86-', '(86)', '（', '）', '&middot;'];

    private static function loadAddressData()
    {
        if (self::$addressList === null) {
            $jsonFile = __DIR__ . '/data/address_code.json';
            if (file_exists($jsonFile)) {
                $content = file_get_contents($jsonFile);
                self::$addressList = json_decode($content, true) ?: [];
                foreach (self::$addressList as &$item) {
                    self::formatAddressList($item, 1, null);
                }
            } else {
                self::$addressList = [];
            }
        }
        return self::$addressList;
    }

    private static function formatAddressList(&$item, $index, $province)
    {
        if ($index === 1) { $item['province'] = $item['name']; $item['type'] = 'province'; }
        if ($index === 2) {
            if ($item['name'] === '市辖区') { $item['name'] = $province['name']; }
            $item['city'] = $item['name']; $item['type'] = 'city';
        }
        if ($index === 3) { $item['county'] = $item['name']; $item['type'] = 'county'; }
        if ($index === 4) { $item['street'] = $item['name']; $item['type'] = 'street'; }
        
        if (isset($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as &$child) {
                self::formatAddressList($child, $index + 1, $item);
            }
        }
    }


    public static function parse($text)
    {
        $result = [
            'name' => '', 'phone' => '', 'idCard' => '',
            'province' => '', 'provinceCode' => '',
            'city' => '', 'cityCode' => '',
            'county' => '', 'countyCode' => '',
            'street' => '', 'streetCode' => '',
            'address' => ''
        ];

        if (empty($text)) { return $result; }

        $addressList = self::loadAddressData();
        $originalText = $text; // 保存原始文本用于提取详细地址
        $text = self::formatByKey($text);
        $copy = $text;
        
        // === 第一步：预处理，提取身份证号 ===
        if (preg_match('/(?<!\d)([1-6]\d{5}(?:19|20)\d{9}[\dXx])(?!\d)/', $text, $idCardMatch)) {
            if (self::isValidIdCard($idCardMatch[1])) {
                $result['idCard'] = strtoupper($idCardMatch[1]);
                $text = str_replace($idCardMatch[1], ' ', $text);
            }
        }
        if (empty($result['idCard']) && preg_match('/(?<!\d)(\d{17}[\dXx])(?!\d)/', $text, $idCardMatch)) {
            if (self::isValidIdCard($idCardMatch[1])) {
                $result['idCard'] = strtoupper($idCardMatch[1]);
                $text = str_replace($idCardMatch[1], ' ', $text);
            }
        }
        
        // === 第二步：预处理，提取手机号和姓名 ===
        $provinceKeywords = '北京|天津|上海|重庆|河北|山西|辽宁|吉林|黑龙江|江苏|浙江|安徽|福建|江西|山东|河南|湖北|湖南|广东|海南|四川|贵州|云南|陕西|甘肃|青海|台湾|内蒙古|广西|西藏|宁夏|新疆|香港|澳门';
        
        if (preg_match('/([\x{4e00}-\x{9fa5}]{2,4})(1[3-9]\d{9})(?=' . $provinceKeywords . '|[\s]|$)/u', $text, $namePhoneMatch)) {
            $result['name'] = $namePhoneMatch[1];
            $result['phone'] = $namePhoneMatch[2];
            $text = str_replace($namePhoneMatch[0], ' ', $text);
        }
        elseif (preg_match('/([\x{4e00}-\x{9fa5}]{2,4})(1[3-9]\d{9})/u', $text, $namePhoneMatch)) {
            $result['name'] = $namePhoneMatch[1];
            $result['phone'] = $namePhoneMatch[2];
            $text = str_replace($namePhoneMatch[0], ' ', $text);
        }
        // 尝试提取独立的手机号
        elseif (preg_match('/(?<![0-9])(1[3-9]\d{9})(?![0-9])/u', $text, $phoneMatch)) {
            $result['phone'] = $phoneMatch[1];
            $text = str_replace($phoneMatch[0], ' ', $text);
        }
        
        $text = self::stripScript($text);
        
        $parts = array_filter(preg_split('/\s+/', $text));
        $remainingParts = []; // 用于收集未被识别的部分作为详细地址
        
        // 先尝试识别并提取省市区（可能是空格分隔的）
        $provincePattern = '/(北京|天津|上海|重庆)(市)?|(河北|山西|辽宁|吉林|黑龙江|江苏|浙江|安徽|福建|江西|山东|河南|湖北|湖南|广东|海南|四川|贵州|云南|陕西|甘肃|青海|台湾)(省)?|(内蒙古|广西|西藏|宁夏|新疆)(自治区|壮族自治区|回族自治区|维吾尔自治区)?|(香港|澳门)(特别行政区)?/u';
        $cityPattern = '/([\x{4e00}-\x{9fa5}]{2,8})(市|地区|州|盟)/u';
        $countyPattern = '/([\x{4e00}-\x{9fa5}]{2,8})(区|县|旗)/u';
        
        $foundProvincePart = null;
        $foundCityPart = null;
        $foundCountyPart = null;
        
        // 第一遍：识别省市区部分
        $partsArray = array_values($parts);
        foreach ($partsArray as $idx => $part) {
            if (preg_match($provincePattern, $part) && $foundProvincePart === null) {
                $foundProvincePart = $idx;
            } elseif (preg_match($cityPattern, $part) && $foundCityPart === null) {
                $foundCityPart = $idx;
            } elseif (preg_match($countyPattern, $part) && $foundCountyPart === null) {
                $foundCountyPart = $idx;
            }
        }
        
        // 如果找到了分散的省市区，合并后一起解析
        $mergedAreaParts = [];
        $areaIndices = [];
        
        if ($foundProvincePart !== null) { $mergedAreaParts[] = $partsArray[$foundProvincePart]; $areaIndices[] = $foundProvincePart; }
        if ($foundCityPart !== null) { $mergedAreaParts[] = $partsArray[$foundCityPart]; $areaIndices[] = $foundCityPart; }
        if ($foundCountyPart !== null) { $mergedAreaParts[] = $partsArray[$foundCountyPart]; $areaIndices[] = $foundCountyPart; }
        
        // 如果找到了省市区部分，合并后用smartAddress解析
        if (!empty($mergedAreaParts)) {
            $mergedArea = implode('', $mergedAreaParts);
            $addressObj = self::smartAddress($mergedArea, $addressList);
            if (!empty($addressObj) && !empty($addressObj['province'])) {
                $result = array_merge($result, $addressObj);
                $result['address'] = ''; // 先清空，后面统一处理
            }
        }

        // === 第四步：从原始文本中提取详细地址 ===
        // 找到区县后面的所有内容作为详细地址
        if (!empty($result['county'])) {
            $detailAddress = self::extractDetailAddressFromText($copy, $result);
            if (!empty($detailAddress)) {
                $result['address'] = $detailAddress;
            }
        }

        // === 第五步：处理剩余部分，识别姓名等 ===
        foreach ($partsArray as $idx => $part) {
            if (empty($part)) continue;
            
            // 跳过已识别为省市区的部分
            if (in_array($idx, $areaIndices) && !empty($result['province'])) {
                continue;
            }
            
            $originalPart = $part;
            if (mb_strlen($part) === 1) { $part .= 'XX'; }
            
            $matched = false;
            
            // 如果省市区还没识别，尝试用smartAddress解析
            if (empty($result['province']) && mb_strlen($part) >= 5) {
                $addressObj = self::smartAddress($part, $addressList);
                if (!empty($addressObj) && !empty($addressObj['province'])) {
                    $result = array_merge($result, $addressObj);
                    $matched = true;
                }
            }
            
            // 识别身份证（如果预处理没识别到）
            if (!$matched && empty($result['idCard']) && self::isValidIdCard($originalPart)) {
                $result['idCard'] = strtoupper($originalPart);
                $matched = true;
            }
            
            // 识别手机号（如果预处理没识别到）
            if (!$matched && empty($result['phone']) && preg_match('/^1[3-9]\d{9}$/', $originalPart)) {
                $result['phone'] = $originalPart;
                $matched = true;
            }
            
            // 识别姓名（2-4个汉字，且还没有识别出姓名）
            if (!$matched && empty($result['name'])) {
                $cleanPart = str_replace('XX', '', $part);
                if (mb_strlen($cleanPart) >= 2 && mb_strlen($cleanPart) <= 4 && self::isChinese($cleanPart)) {
                    $result['name'] = $cleanPart;
                    $matched = true;
                }
            }
        }

        // 补充识别手机号（可能带分隔符）
        if (empty($result['phone']) && preg_match('/1[3-9]\d{9}/', $copy, $matches)) {
            $result['phone'] = $matches[0];
        }

        return $result;
    }
    
    /**
     * 从原始文本中提取详细地址
     */
    private static function extractDetailAddressFromText($text, $result)
    {
        // 移除空格，合并成完整字符串
        $cleanText = preg_replace('/\s+/', '', $text);
        
        // 移除姓名、手机号、身份证
        if (!empty($result['name'])) {
            $cleanText = str_replace($result['name'], '', $cleanText);
        }
        if (!empty($result['phone'])) {
            $cleanText = str_replace($result['phone'], '', $cleanText);
        }
        if (!empty($result['idCard'])) {
            $cleanText = str_replace($result['idCard'], '', $cleanText);
        }
        
        // 找到区县的第一次出现位置，提取后面的所有内容作为详细地址
        $county = isset($result['county']) ? $result['county'] : '';
        if (!empty($county)) {
            // 尝试多种匹配方式
            $countyVariants = array(
                $county,
                preg_replace('/[区县市]$/', '', $county) . '区',
                preg_replace('/[区县市]$/', '', $county) . '县',
                preg_replace('/[区县市]$/', '', $county)
            );
            
            foreach ($countyVariants as $variant) {
                // 找到第一次出现的位置（区县后面的所有内容都是详细地址）
                $pos = mb_strpos($cleanText, $variant);
                if ($pos !== false) {
                    $detail = mb_substr($cleanText, $pos + mb_strlen($variant));
                    if (!empty(trim($detail))) {
                        return trim($detail);
                    }
                }
            }
        }
        
        // 如果没有区县，尝试从城市后面提取
        $city = isset($result['city']) ? $result['city'] : '';
        if (!empty($city)) {
            $pos = mb_strpos($cleanText, $city);
            if ($pos !== false) {
                $detail = mb_substr($cleanText, $pos + mb_strlen($city));
                if (!empty(trim($detail))) {
                    return trim($detail);
                }
            }
        }
        
        return '';
    }

    private static function smartAddress($address, $addressList)
    {
        $result = [];
        $originalAddress = $address; // 保存原始地址用于提取详细地址
        $address = self::stripScript($address);

        if (self::isValidIdCard($address)) {
            $result['idCard'] = strtoupper($address);
            return $result;
        }

        if (preg_match('/1[3-9]\d{9}/', $address, $matches)) {
            $result['phone'] = $matches[0];
            $address = str_replace($matches[0], '', $address);
        }

        // 记录匹配到的省市区位置，用于后续提取详细地址
        $matchedPositions = [];

        $matchProvince = [];
        for ($i = 0; $i < mb_strlen($address); $i++) {
            $matchAddress = mb_substr($address, 0, $i + 2);
            foreach ($addressList as $item) {
                if (isset($item['province']) && mb_strpos($item['province'], $matchAddress) !== false) {
                    $matchProvince[] = ['province' => $item['province'], 'provinceCode' => $item['code'], 'matchValue' => $matchAddress];
                }
            }
        }

        if (!empty($matchProvince)) {
            $best = self::getBestMatch($matchProvince, 'province');
            $result['province'] = $best['province'];
            $result['provinceCode'] = $best['provinceCode'];
            $matchedPositions[] = $best['matchValue'];
            $address = str_replace($best['matchValue'], '', $address);
        }

        $matchCity = [];
        $isDirect = in_array(isset($result['province']) ? $result['province'] : '', array('北京市', '天津市', '上海市', '重庆市'));
        
        for ($i = 0; $i < mb_strlen($address); $i++) {
            $matchAddress = mb_substr($address, 0, $i + 2);
            foreach ($addressList as $prov) {
                if (isset($result['provinceCode']) && $prov['code'] !== $result['provinceCode']) { continue; }
                if (!isset($prov['children'])) continue;
                
                if ($isDirect) {
                    foreach ($prov['children'] as $city) {
                        if (!isset($city['children'])) continue;
                        foreach ($city['children'] as $county) {
                            if (isset($county['county']) && mb_strpos($county['county'], $matchAddress) !== false) {
                                $matchCity[] = [
                                    'county' => $county['county'], 'countyCode' => $county['code'],
                                    'city' => $city['city'] ?? $city['name'], 'cityCode' => $city['code'],
                                    'matchValue' => $matchAddress, 'province' => $prov['province'], 'provinceCode' => $prov['code']
                                ];
                            }
                        }
                    }
                } else {
                    foreach ($prov['children'] as $city) {
                        if (isset($city['city']) && mb_strpos($city['city'], $matchAddress) !== false) {
                            $matchCity[] = [
                                'city' => $city['city'], 'cityCode' => $city['code'],
                                'matchValue' => $matchAddress, 'province' => $prov['province'], 'provinceCode' => $prov['code']
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($matchCity)) {
            $best = self::getBestMatch($matchCity, 'city');
            $result['city'] = $best['city'];
            $result['cityCode'] = $best['cityCode'];
            $matchedPositions[] = $best['matchValue'];
            if (isset($best['county'])) { 
                $result['county'] = $best['county']; 
                $result['countyCode'] = $best['countyCode']; 
                $matchedPositions[] = $best['county'];
            }
            if (empty($result['province'])) { $result['province'] = $best['province']; $result['provinceCode'] = $best['provinceCode']; }
            $address = str_replace($best['matchValue'], '', $address);
        }

        // 非直辖市需要单独匹配区县
        if (!$isDirect && !empty($result['city']) && empty($result['county'])) {
            $matchCounty = [];
            for ($i = 0; $i < mb_strlen($address); $i++) {
                $matchAddress = mb_substr($address, 0, $i + 2);
                foreach ($addressList as $prov) {
                    if ($prov['code'] !== ($result['provinceCode'] ?? '')) continue;
                    if (!isset($prov['children'])) continue;
                    
                    foreach ($prov['children'] as $city) {
                        if ($city['code'] !== ($result['cityCode'] ?? '')) continue;
                        if (!isset($city['children'])) continue;
                        
                        foreach ($city['children'] as $county) {
                            if (isset($county['county']) && mb_strpos($county['county'], $matchAddress) !== false) {
                                $matchCounty[] = [
                                    'county' => $county['county'], 
                                    'countyCode' => $county['code'],
                                    'matchValue' => $matchAddress
                                ];
                            }
                        }
                    }
                }
            }
            
            if (!empty($matchCounty)) {
                $best = self::getBestMatch($matchCounty, 'county');
                $result['county'] = $best['county'];
                $result['countyCode'] = $best['countyCode'];
                $matchedPositions[] = $best['matchValue'];
                $address = str_replace($best['matchValue'], '', $address);
            }
        }

        // 提取详细地址：从原始地址中找到区县后面的所有内容
        if (!empty($result['province'])) { 
            $detailAddress = self::extractDetailAddress($originalAddress, $result);
            $result['address'] = $detailAddress ?: trim($address);
        }
        return $result;
    }
    
    /**
     * 从原始地址中提取详细地址（区县之后的部分）
     */
    private static function extractDetailAddress($originalAddress, $result)
    {
        // 清理原始地址中的空格
        $cleanAddress = preg_replace('/\s+/', '', $originalAddress);
        
        // 按优先级查找：区县 > 城市 > 省份
        $searchKeys = [];
        if (!empty($result['county'])) {
            // 区县名可能带或不带"区/县/市"后缀
            $countyBase = preg_replace('/[区县市]$/', '', $result['county']);
            $searchKeys[] = $result['county'];
            $searchKeys[] = $countyBase . '区';
            $searchKeys[] = $countyBase . '县';
            $searchKeys[] = $countyBase;
        }
        if (!empty($result['city'])) {
            $searchKeys[] = $result['city'];
        }
        
        foreach ($searchKeys as $key) {
            $pos = mb_strpos($cleanAddress, $key);
            if ($pos !== false) {
                $detail = mb_substr($cleanAddress, $pos + mb_strlen($key));
                // 清理详细地址开头可能残留的省市区名称
                $detail = self::cleanDetailAddressPrefix($detail, $result);
                if (!empty($detail)) {
                    return trim($detail);
                }
            }
        }
        
        return '';
    }
    
    /**
     * 清理详细地址开头可能残留的省市区名称
     */
    private static function cleanDetailAddressPrefix($detail, $result)
    {
        // 移除开头可能重复的省市区名
        $prefixes = [];
        if (!empty($result['province'])) {
            $prefixes[] = $result['province'];
            $prefixes[] = preg_replace('/[省市]$/', '', $result['province']);
        }
        if (!empty($result['city'])) {
            $prefixes[] = $result['city'];
            $prefixes[] = preg_replace('/[市]$/', '', $result['city']);
        }
        if (!empty($result['county'])) {
            $prefixes[] = $result['county'];
            $prefixes[] = preg_replace('/[区县市]$/', '', $result['county']);
        }
        
        foreach ($prefixes as $prefix) {
            if (mb_strpos($detail, $prefix) === 0) {
                $detail = mb_substr($detail, mb_strlen($prefix));
            }
        }
        
        return $detail;
    }


    private static function getBestMatch($matches, $key)
    {
        foreach ($matches as &$item) {
            $item['score'] = 0;
            foreach ($matches as $other) {
                if ($item[$key] === $other[$key]) {
                    $item['score']++;
                    if (mb_strlen($item['matchValue']) < mb_strlen($other['matchValue'])) {
                        $item['matchValue'] = $other['matchValue'];
                    }
                }
            }
        }
        usort($matches, function($a, $b) { return $b['score'] - $a['score']; });
        return $matches[0];
    }

    private static function formatByKey($text)
    {
        // 先把换行符替换为空格，保留分隔
        $text = preg_replace('/[\r\n\t]+/', ' ', $text);
        foreach (self::$keyM as $key) { $text = preg_replace('/' . preg_quote($key, '/') . '[：:\s]*/u', ' ', $text); }
        foreach (self::$keyA as $key) { $text = preg_replace('/' . preg_quote($key, '/') . '[：:\s]*/u', ' ', $text); }
        foreach (self::$keyD as $key) { $text = str_replace($key, '', $text); }
        return trim($text);
    }

    private static function stripScript($text)
    {
        $text = preg_replace('/(\d{3})-(\d{4})-(\d{4})/', '$1$2$3', $text);
        $text = preg_replace('/(\d{3}) (\d{4}) (\d{4})/', '$1$2$3', $text);
        $text = preg_replace('/[`~!@$^&*()=|{}\':;,\[\].<>\/?]/u', ' ', $text);
        return preg_replace('/[\r\n]/', ' ', $text);
    }

    private static function isValidIdCard($code)
    {
        if (!preg_match('/^\d{17}[\dXx]$/i', $code)) { return false; }
        $city = ['11'=>1,'12'=>1,'13'=>1,'14'=>1,'15'=>1,'21'=>1,'22'=>1,'23'=>1,'31'=>1,'32'=>1,'33'=>1,'34'=>1,'35'=>1,'36'=>1,'37'=>1,'41'=>1,'42'=>1,'43'=>1,'44'=>1,'45'=>1,'46'=>1,'50'=>1,'51'=>1,'52'=>1,'53'=>1,'54'=>1,'61'=>1,'62'=>1,'63'=>1,'64'=>1,'65'=>1,'71'=>1,'81'=>1,'82'=>1,'91'=>1];
        if (!isset($city[substr($code, 0, 2)])) { return false; }
        $code = strtoupper($code);
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $parity = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;
        for ($i = 0; $i < 17; $i++) { $sum += intval($code[$i]) * $factor[$i]; }
        return $parity[$sum % 11] === $code[17];
    }

    private static function isChinese($str) { return preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $str); }

    public static function parseSimple($text)
    {
        $result = ['name'=>'', 'phone'=>'', 'idcard'=>'', 'province'=>'', 'city'=>'', 'district'=>'', 'address'=>''];
        if (empty($text)) { return $result; }

        $text = self::formatByKey($text);
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (preg_match('/1[3-9]\d[\s\-]?\d{4}[\s\-]?\d{4}/', $text, $m)) {
            $result['phone'] = preg_replace('/[\s\-]/', '', $m[0]);
            $text = str_replace($m[0], ' ', $text);
        }
        if (preg_match('/\d{17}[\dXx]/', $text, $m)) {
            $result['idcard'] = strtoupper($m[0]);
            $text = str_replace($m[0], ' ', $text);
        }

        $provinceRegex = '/(北京|天津|上海|重庆)(市)?|(河北|山西|辽宁|吉林|黑龙江|江苏|浙江|安徽|福建|江西|山东|河南|湖北|湖南|广东|海南|四川|贵州|云南|陕西|甘肃|青海|台湾)(省)?|(内蒙古|广西壮族|西藏|宁夏回族|新疆维吾尔)(自治区)?|(香港|澳门)(特别行政区)?/u';
        if (preg_match($provinceRegex, $text, $m)) { $result['province'] = $m[0]; $text = str_replace($m[0], '', $text); }
        if (preg_match('/([\x{4e00}-\x{9fa5}]{2,8})(市|地区|州|盟)/u', $text, $m)) { $result['city'] = $m[0]; $text = str_replace($m[0], '', $text); }
        if (preg_match('/([\x{4e00}-\x{9fa5}]{2,8})(区|县|旗)/u', $text, $m)) { $result['district'] = $m[0]; $text = str_replace($m[0], '', $text); }

        $parts = array_filter(preg_split('/\s+/', trim($text)));
        foreach ($parts as $part) {
            if (mb_strlen($part) >= 2 && mb_strlen($part) <= 4 && self::isChinese($part) && empty($result['name'])) {
                $result['name'] = $part;
            } else {
                $result['address'] .= $part;
            }
        }
        return $result;
    }
}
