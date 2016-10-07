<?php

namespace suver\behavior\upload;



interface  ImageModifedInterface
{
    public function execute($source_path, $distinct_path, $params = []);
}
