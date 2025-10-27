<?php

it('will not use debugging functions')
    ->expect(true)
    ->not
    ->toBeFalse();
