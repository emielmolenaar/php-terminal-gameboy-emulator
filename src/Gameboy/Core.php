<?php
namespace GameBoy;

class Core
{
    // Canvas DOM object for drawing out the graphics to.
    protected $canvas;

    // Image DOM object for drawing out the graphics to as an alternate means.
    protected $canvasAlt;

    // Used for external scripts to tell if we're really using the canvas or not (Helpful with fullscreen switching).
    public $canvasFallbackHappened = false;

    // LCD Context
    public $drawContext = null;

    //The game's ROM.
    public $ROMImage;

    //The full ROM file dumped to an array.
    public $ROM = [];

    //Whether we're in the GBC boot ROM.
    public $inBootstrap = true;

    //Updated upon ROM loading...
    public $usedBootROM = false;

    // Accumulator (default is GB mode)
    public $registerA = 0x01;

    // bit 7 - Zero
    public $FZero = true;

    // bit 6 - Sub
    public $FSubtract = false;

    // bit 5 - Half Carry
    public $FHalfCarry = true;

    // bit 4 - Carry
    public $FCarry = true;

    // Register B
    public $registerB = 0x00;

    // Register C
    public $registerC = 0x13;

    // Register D
    public $registerD = 0x00;

    // Register E
    public $registerE = 0xD8;

    // Registers H and L
    public $registersHL = 0x014D;

    //Array of functions mapped to read back memory
    public $memoryReader = [];

    //Array of functions mapped to write to memory
    public $memoryWriter = [];

    // Stack Pointer
    public $stackPointer = 0xFFFE;

    // Program Counter
    public $programCounter = 0x0100;

    //Has the CPU been suspended until the next interrupt?
    public $halt = false;

    //Did we trip the DMG Halt bug?
    public $skipPCIncrement = false;

    //Has the emulation been paused or a frame has ended?
    public $stopEmulator = 3;

    //Are interrupts enabled?
    public $IME = true;

    //HDMA Transfer Flag - GBC only
    public $hdmaRunning = false;

    //The number of clock cycles emulated.
    public $CPUTicks = 0;

    //GBC Speed Multiplier
    public $multiplier = 1;

    //
    //Main RAM, MBC RAM, GBC Main RAM, VRAM, etc.
    //

    //Main Core Memory
    public $memory = [];

    //Switchable RAM (Used by games for more RAM) for the main memory range 0xA000 - 0xC000.
    public $MBCRam = [];

    //Extra VRAM bank for GBC.
    public $VRAM = [];

    //Current VRAM bank for GBC.
    public $currVRAMBank = 0;

    //GBC main RAM Banks
    public $GBCMemory = [];

    //MBC1 Type (4/32, 16/8)
    public $MBC1Mode = false;

    //MBC RAM Access Control.
    public $MBCRAMBanksEnabled = false;

    //MBC Currently Indexed RAM Bank
    public $currMBCRAMBank = 0;

    //MBC Position Adder;
    public $currMBCRAMBankPosition = -0xA000;

    //GameBoy Color detection.
    public $cGBC = false;

    //Currently Switched GameBoy Color ram bank
    public $gbcRamBank = 1;

    //GBC RAM offset from address start.
    public $gbcRamBankPosition = -0xD000;

    //GBC RAM (ECHO mirroring) offset from address start.
    public $gbcRamBankPositionECHO = -0xF000;

    //Used to map the RAM banks to maximum size the MBC used can do.
    public $RAMBanks = [0, 1, 2, 4, 16];

    //Offset of the ROM bank switching.
    public $ROMBank1offs = 0;

    //The parsed current ROM bank selection.
    public $currentROMBank = 0;

    //Cartridge Type
    public $cartridgeType = 0;

    //Name of the game
    public $name = "";

    //Game code (Suffix for older games)
    public $gameCode = "";

    //A boolean to see if this was loaded in as a save state.
    public $fromSaveState = false;

    //When loaded in as a save state, this will not be empty.
    public $savedStateFileName = "";

    //Tracker for STAT triggering.
    public $STATTracker = 0;

    //The scan line mode (for lines 1-144 it's 2-3-0, for 145-154 it's 1)
    public $modeSTAT = 0;

    //Should we trigger an interrupt if LY==LYC?
    public $LYCMatchTriggerSTAT = false;

    //Should we trigger an interrupt if in mode 2?
    public $mode2TriggerSTAT = false;

    //Should we trigger an interrupt if in mode 1?
    public $mode1TriggerSTAT = false;

    //Should we trigger an interrupt if in mode 0?
    public $mode0TriggerSTAT = false;

    //Is the emulated LCD controller on?
    public $LCDisOn = false;

    //Array of functions to handle each scan line we do (onscreen + offscreen)
    public $LINECONTROL;

    public $DISPLAYOFFCONTROL = [];

    //Pointer to either LINECONTROL or DISPLAYOFFCONTROL.
    public $LCDCONTROL = null;

    public $gfxWindowY = false;

    public $gfxWindowDisplay = false;

    public $gfxSpriteShow = false;

    public $gfxSpriteDouble = false;

    public $gfxBackgroundY = false;

    public $gfxBackgroundX = false;

    public $TIMAEnabled = false;

    //Joypad State (two four-bit states actually)
    public $JoyPad = 0xFF;

    //
    //RTC:
    //
    public $RTCisLatched = true;

    public $latchedSeconds = 0;

    public $latchedMinutes = 0;

    public $latchedHours = 0;

    public $latchedLDays = 0;

    public $latchedHDays = 0;

    public $RTCSeconds = 0;

    public $RTCMinutes = 0;

    public $RTCHours = 0;

    public $RTCDays = 0;

    public $RTCDayOverFlow = false;

    public $RTCHALT = false;

    //
    //Sound variables:
    //

    //Audio object or the WAV PCM generator wrapper
    public $audioHandle = null;

    //Buffering counter for the WAVE PCM output.
    public $outTracker = 0;

    //Buffering limiter for WAVE PCM output.
    public $outTrackerLimit = 0;

    //Length of the sound buffers.
    public $numSamplesTotal = 0;

    //Length of the sound buffer for one channel.
    public $sampleSize = 0;

    public $dutyLookup = [0.125, 0.25, 0.5, 0.75];

    //The audio buffer we're working on (When not overflowing).
    public $audioSamples = [];

    //Audio overflow buffer.
    public $audioBackup = [];

    //Pointer to the sample workbench.
    public $currentBuffer = null;

    //How many channels are being fed into the left side stereo / mono.
    public $channelLeftCount = 0;

    //How many channels are being fed into the right side stereo.
    public $channelRightCount = 0;

    public $noiseTableLookup = null;

    public $smallNoiseTable = [0x80];

    public $largeNoiseTable = [0x8000];

    //As its name implies
    public $soundMasterEnabled = false;

    //Track what method we're using for audio output.
    public $audioType = -1;

    //
    //Vin Shit:
    //

    public $VinLeftChannelEnabled = false;

    public $VinRightChannelEnabled = false;

    public $VinLeftChannelMasterVolume = 0;

    public $VinRightChannelMasterVolume = 0;

    public $vinLeft = 1;

    public $vinRight = 1;

    //Channels Enabled:

    //Which channels are enabled for left side stereo / mono?
    public $leftChannel = [true, true, true, false];

    //Which channels are enabled for right side stereo?
    public $rightChannel = [true, true, true, false];


    //Current Samples Being Computed:
    public $currentSampleLeft = 0;

    public $currentSampleRight = 0;

    public $channel3Tracker = 0;

    //
    //Pre-multipliers to cache some calculations:
    //
    public $preChewedAudioComputationMultiplier;

    public $preChewedWAVEAudioComputationMultiplier;

    public $whiteNoiseFrequencyPreMultiplier;

    //Premultiplier for audio samples per instructions.
    public $samplesOut = 0;

    public $volumeEnvelopePreMultiplier;

    public $channel1TimeSweepPreMultiplier;

    public $channel1lastTotalLength;

    public $channel1lastTimeSweep;

    public $channel1frequency;

    public $channel2lastTotalLength;

    public $channel2frequency;

    public $channel3frequency;

    public $channel4lastTotalLength;

    public $audioTotalLengthMultiplier;

    //
    //Audio generation counters:
    //

    public $audioOverflow = false;

    //Used to sample the audio system every x CPU instructions.
    public $audioTicks = 0;

    //Used to keep alignment on audio generation.
    public $audioIndex = 0;

    //Used to keep alignment on the number of samples to output (Realign from counter alias).
    public $rollover = 0;

    //
    //Timing Variables
    //

    //Times for how many instructions to execute before ending the loop.
    public $emulatorTicks = 0;

    // DIV Ticks Counter (Invisible lower 8-bit)
    public $DIVTicks = 14;

    // ScanLine Counter
    public $LCDTicks = 15;

    // Timer Ticks Count
    public $timerTicks = 0;

    // Timer Max Ticks
    public $TACClocker = 256;

    //Are the interrupts on queue to be enabled?
    public $untilEnable = 0;

    //The last time we iterated the main loop.
    public $lastIteration = 0;

    //Actual scan line...
    public $actualScanLine = 0;

    //
    //ROM Cartridge Components:
    //

    //Does the cartridge use MBC1?
    public $cMBC1 = false;

    //Does the cartridge use MBC2?
    public $cMBC2 = false;

    //Does the cartridge use MBC3?
    public $cMBC3 = false;

    //Does the cartridge use MBC5?
    public $cMBC5 = false;

    //Does the cartridge use save RAM?
    public $cSRAM = false;

    public $cMMMO1 = false;

    //Does the cartridge use the RUMBLE addressing (modified MBC5)?
    public $cRUMBLE = false;

    public $cCamera = false;

    public $cTAMA5 = false;

    public $cHuC3 = false;

    public $cHuC1 = false;

    // 1 Bank = 16 KBytes = 256 Kbits
    public $ROMBanks = [
        2, 4, 8, 16, 32, 64, 128, 256, 512
    ];

    //How many RAM banks were actually allocated?
    public $numRAMBanks = 0;

    //
    //Graphics Variables
    //

    //To prevent the repeating of drawing a blank screen.
    public $drewBlank = 0;

    // tile data arrays
    public $tileData = [];

    public $frameBuffer = [];

    public $scaledFrameBuffer = [];

    public $canvasBuffer;

    public $gbcRawPalette = [];

    //GB: 384, GBC: 384 * 2
    public $tileCount = 384;

    public $tileCountInvalidator;

    public $colorCount = 12;

    public $gbPalette = [];

    public $gbColorizedPalette = [];

    public $gbcPalette = [];

    // min "attrib" value where transparency can occur (Default is 4 (GB mode))
    public $transparentCutoff = 4;

    public $bgEnabled = true;

    public $spritePriorityEnabled = true;

    // true if there are any images to be invalidated
    public $tileReadState = [];

    public $windowSourceLine = 0;

    //"Classic" GameBoy palette colors.
    public $colors = [0x80EFFFDE, 0x80ADD794, 0x80529273, 0x80183442];

    //Frame skip tracker
    public $frameCount;

    public $weaveLookup = [];

    public $width = 160;

    public $height = 144;

    public $pixelCount;

    public $rgbCount;

    public $widthRatio;

    public $heightRatio;

    //Pointer to the current palette we're using (Used for palette switches during boot or so it can be done anytime)
    public $palette = null;

    //
    //Data
    //

    public $DAATable;

    public $GBCBOOTROM;

    public $ffxxDump;

    public $OPCODE;

    public $CBOPCODE;

    public $TICKTable;

    public $SecondaryTICKTable;

    // Added

    public $cTIMER = null;

    public function __construct($canvas, $canvasAlt, $ROMImage)
    {
        $this->canvas = $canvas;
        $this->canvasAlt = $canvasAlt;
        $this->ROMImage = $ROMImage;

        $this->DISPLAYOFFCONTROL[] = function ($parentObj) {
            //Array of line 0 function to handle the LCD controller when it's off (Do nothing!).
        };

        $this->preChewedAudioComputationMultiplier = 0x20000 / Settings::$settings[14];
        $this->preChewedWAVEAudioComputationMultiplier = 0x200000 / Settings::$settings[14];
        $this->whiteNoiseFrequencyPreMultiplier = 4194300 / Settings::$settings[14] / 8;

        $this->volumeEnvelopePreMultiplier = Settings::$settings[14] / 0x40;
        $this->channel1TimeSweepPreMultiplier = Settings::$settings[14] / 0x80;
        $this->audioTotalLengthMultiplier = Settings::$settings[14] / 0x100;

        $this->tileCountInvalidator = $this->tileCount * 4;

        $this->ROMBanks[0x52] = 72;
        $this->ROMBanks[0x53] = 80;
        $this->ROMBanks[0x54] = 96;

        $this->frameCount = Settings::$settings[12];
        $this->pixelCount = $this->width * $this->height;
        $this->rgbCount = $this->pixelCount * 4;
        $this->widthRatio = 160 / $this->width;
        $this->heightRatio = 144 / $this->height;

        // Copy Data
        $this->DAATable = Data::$DAATable;
        $this->GBCBOOTROM = Data::$GBCBOOTROM;
        $this->ffxxDump = Data::$ffxxDump;

        $opcode = new Opcode();
        $this->OPCODE = $opcode->get();

        $cbopcode = new Cbopcode();
        $this->CBOPCODE = $cbopcode->get();

        $this->TICKTable = TICKTables::$primary;
        $this->SecondaryTICKTable = TICKTables::$secondary;

        $this->LINECONTROL = array_fill(0, 154, null);

        // $this->initializeStartState();
    }

    public function saveState()
    {
        return [
            $this->fromTypedArray($this->ROM),
            $this->inBootstrap,
            $this->registerA,
            $this->FZero,
            $this->FSubtract,
            $this->FHalfCarry,
            $this->FCarry,
            $this->registerB,
            $this->registerC,
            $this->registerD,
            $this->registerE,
            $this->registersHL,
            $this->stackPointer,
            $this->programCounter,
            $this->halt,
            $this->IME,
            $this->hdmaRunning,
            $this->CPUTicks,
            $this->multiplier,
            $this->fromTypedArray($this->memory),
            $this->fromTypedArray($this->MBCRam),
            $this->fromTypedArray($this->VRAM),
            $this->currVRAMBank,
            $this->fromTypedArray($this->GBCMemory),
            $this->MBC1Mode,
            $this->MBCRAMBanksEnabled,
            $this->currMBCRAMBank,
            $this->currMBCRAMBankPosition,
            $this->cGBC,
            $this->gbcRamBank,
            $this->gbcRamBankPosition,
            $this->ROMBank1offs,
            $this->currentROMBank,
            $this->cartridgeType,
            $this->name,
            $this->gameCode,
            $this->modeSTAT,
            $this->LYCMatchTriggerSTAT,
            $this->mode2TriggerSTAT,
            $this->mode1TriggerSTAT,
            $this->mode0TriggerSTAT,
            $this->LCDisOn,
            $this->gfxWindowY,
            $this->gfxWindowDisplay,
            $this->gfxSpriteShow,
            $this->gfxSpriteDouble,
            $this->gfxBackgroundY,
            $this->gfxBackgroundX,
            $this->TIMAEnabled,
            $this->DIVTicks,
            $this->LCDTicks,
            $this->timerTicks,
            $this->TACClocker,
            $this->untilEnable,
            $this->lastIteration,
            $this->cMBC1,
            $this->cMBC2,
            $this->cMBC3,
            $this->cMBC5,
            $this->cSRAM,
            $this->cMMMO1,
            $this->cRUMBLE,
            $this->cCamera,
            $this->cTAMA5,
            $this->cHuC3,
            $this->cHuC1,
            $this->drewBlank,
            $this->tileData.slice(0),
            $this->fromTypedArray($this->frameBuffer),
            $this->tileCount,
            $this->colorCount,
            $this->gbPalette,
            $this->gbcRawPalette,
            $this->gbcPalette,
            $this->transparentCutoff,
            $this->bgEnabled,
            $this->spritePriorityEnabled,
            $this->fromTypedArray($this->tileReadState),
            $this->windowSourceLine,
            $this->channel1adjustedFrequencyPrep,
            $this->channel1duty,
            $this->channel1lastSampleLookup,
            $this->channel1adjustedDuty,
            $this->channel1totalLength,
            $this->channel1envelopeVolume,
            $this->channel1currentVolume,
            $this->channel1envelopeType,
            $this->channel1envelopeSweeps,
            $this->channel1consecutive,
            $this->channel1frequency,
            $this->channel1volumeEnvTime,
            $this->channel1lastTotalLength,
            $this->channel1timeSweep,
            $this->channel1lastTimeSweep,
            $this->channel1numSweep,
            $this->channel1frequencySweepDivider,
            $this->channel1decreaseSweep,
            $this->channel2adjustedFrequencyPrep,
            $this->channel2duty,
            $this->channel2lastSampleLookup,
            $this->channel2adjustedDuty,
            $this->channel2totalLength,
            $this->channel2envelopeVolume,
            $this->channel2currentVolume,
            $this->channel2envelopeType,
            $this->channel2envelopeSweeps,
            $this->channel2consecutive,
            $this->channel2frequency,
            $this->channel2volumeEnvTime,
            $this->channel2lastTotalLength,
            $this->channel3canPlay,
            $this->channel3totalLength,
            $this->channel3lastTotalLength,
            $this->channel3patternType,
            $this->channel3frequency,
            $this->channel3consecutive,
            $this->channel3PCM,
            $this->channel3adjustedFrequencyPrep,
            $this->channel4adjustedFrequencyPrep,
            $this->channel4lastSampleLookup,
            $this->channel4totalLength,
            $this->channel4envelopeVolume,
            $this->channel4currentVolume,
            $this->channel4envelopeType,
            $this->channel4envelopeSweeps,
            $this->channel4consecutive,
            $this->channel4volumeEnvTime,
            $this->channel4lastTotalLength,
            $this->soundMasterEnabled,
            $this->VinLeftChannelEnabled,
            $this->VinRightChannelEnabled,
            $this->VinLeftChannelMasterVolume,
            $this->VinRightChannelMasterVolume,
            $this->vinLeft,
            $this->vinRight,
            $this->leftChannel,
            $this->rightChannel,
            $this->actualScanLine,
            $this->RTCisLatched,
            $this->latchedSeconds,
            $this->latchedMinutes,
            $this->latchedHours,
            $this->latchedLDays,
            $this->latchedHDays,
            $this->RTCSeconds,
            $this->RTCMinutes,
            $this->RTCHours,
            $this->RTCDays,
            $this->RTCDayOverFlow,
            $this->RTCHALT,
            $this->gbColorizedPalette,
            $this->usedBootROM,
            $this->skipPCIncrement,
            $this->STATTracker,
            $this->gbcRamBankPositionECHO,
            $this->numRAMBanks
        ];
    }

    public function returnFromState($returnedFrom)
    {
        $index = 0;
        $state = $returnedFrom->slice(0);

        $this->ROM = $this->toTypedArray($state[$index++], false, false);
        $this->inBootstrap = $state[$index++];
        $this->registerA = $state[$index++];
        $this->FZero = $state[$index++];
        $this->FSubtract = $state[$index++];
        $this->FHalfCarry = $state[$index++];
        $this->FCarry = $state[$index++];
        $this->registerB = $state[$index++];
        $this->registerC = $state[$index++];
        $this->registerD = $state[$index++];
        $this->registerE = $state[$index++];
        $this->registersHL = $state[$index++];
        $this->stackPointer = $state[$index++];
        $this->programCounter = $state[$index++];
        $this->halt = $state[$index++];
        $this->IME = $state[$index++];
        $this->hdmaRunning = $state[$index++];
        $this->CPUTicks = $state[$index++];
        $this->multiplier = $state[$index++];
        $this->memory = $this->toTypedArray($state[$index++], false, false);
        $this->MBCRam = $this->toTypedArray($state[$index++], false, false);
        $this->VRAM = $this->toTypedArray($state[$index++], false, false);
        $this->currVRAMBank = $state[$index++];
        $this->GBCMemory = $this->toTypedArray($state[$index++], false, false);
        $this->MBC1Mode = $state[$index++];
        $this->MBCRAMBanksEnabled = $state[$index++];
        $this->currMBCRAMBank = $state[$index++];
        $this->currMBCRAMBankPosition = $state[$index++];
        $this->cGBC = $state[$index++];
        $this->gbcRamBank = $state[$index++];
        $this->gbcRamBankPosition = $state[$index++];
        $this->ROMBank1offs = $state[$index++];
        $this->currentROMBank = $state[$index++];
        $this->cartridgeType = $state[$index++];
        $this->name = $state[$index++];
        $this->gameCode = $state[$index++];
        $this->modeSTAT = $state[$index++];
        $this->LYCMatchTriggerSTAT = $state[$index++];
        $this->mode2TriggerSTAT = $state[$index++];
        $this->mode1TriggerSTAT = $state[$index++];
        $this->mode0TriggerSTAT = $state[$index++];
        $this->LCDisOn = $state[$index++];
        $this->gfxWindowY = $state[$index++];
        $this->gfxWindowDisplay = $state[$index++];
        $this->gfxSpriteShow = $state[$index++];
        $this->gfxSpriteDouble = $state[$index++];
        $this->gfxBackgroundY = $state[$index++];
        $this->gfxBackgroundX = $state[$index++];
        $this->TIMAEnabled = $state[$index++];
        $this->DIVTicks = $state[$index++];
        $this->LCDTicks = $state[$index++];
        $this->timerTicks = $state[$index++];
        $this->TACClocker = $state[$index++];
        $this->untilEnable = $state[$index++];
        $this->lastIteration = $state[$index++];
        $this->cMBC1 = $state[$index++];
        $this->cMBC2 = $state[$index++];
        $this->cMBC3 = $state[$index++];
        $this->cMBC5 = $state[$index++];
        $this->cSRAM = $state[$index++];
        $this->cMMMO1 = $state[$index++];
        $this->cRUMBLE = $state[$index++];
        $this->cCamera = $state[$index++];
        $this->cTAMA5 = $state[$index++];
        $this->cHuC3 = $state[$index++];
        $this->cHuC1 = $state[$index++];
        $this->drewBlank = $state[$index++];
        $this->tileData = $state[$index++];
        $this->frameBuffer = $this->toTypedArray($state[$index++], true, false);
        $this->tileCount = $state[$index++];
        $this->colorCount = $state[$index++];
        $this->gbPalette = $state[$index++];
        $this->gbcRawPalette = $state[$index++];
        $this->gbcPalette = $state[$index++];
        $this->transparentCutoff = $state[$index++];
        $this->bgEnabled = $state[$index++];
        $this->spritePriorityEnabled = $state[$index++];
        $this->tileReadState = $this->toTypedArray($state[$index++], false, false);
        $this->windowSourceLine = $state[$index++];
        $this->channel1adjustedFrequencyPrep = $state[$index++];
        $this->channel1duty = $state[$index++];
        $this->channel1lastSampleLookup = $state[$index++];
        $this->channel1adjustedDuty = $state[$index++];
        $this->channel1totalLength = $state[$index++];
        $this->channel1envelopeVolume = $state[$index++];
        $this->channel1currentVolume = $state[$index++];
        $this->channel1envelopeType = $state[$index++];
        $this->channel1envelopeSweeps = $state[$index++];
        $this->channel1consecutive = $state[$index++];
        $this->channel1frequency = $state[$index++];
        $this->channel1volumeEnvTime = $state[$index++];
        $this->channel1lastTotalLength = $state[$index++];
        $this->channel1timeSweep = $state[$index++];
        $this->channel1lastTimeSweep = $state[$index++];
        $this->channel1numSweep = $state[$index++];
        $this->channel1frequencySweepDivider = $state[$index++];
        $this->channel1decreaseSweep = $state[$index++];
        $this->channel2adjustedFrequencyPrep = $state[$index++];
        $this->channel2duty = $state[$index++];
        $this->channel2lastSampleLookup = $state[$index++];
        $this->channel2adjustedDuty = $state[$index++];
        $this->channel2totalLength = $state[$index++];
        $this->channel2envelopeVolume = $state[$index++];
        $this->channel2currentVolume = $state[$index++];
        $this->channel2envelopeType = $state[$index++];
        $this->channel2envelopeSweeps = $state[$index++];
        $this->channel2consecutive = $state[$index++];
        $this->channel2frequency = $state[$index++];
        $this->channel2volumeEnvTime = $state[$index++];
        $this->channel2lastTotalLength = $state[$index++];
        $this->channel3canPlay = $state[$index++];
        $this->channel3totalLength = $state[$index++];
        $this->channel3lastTotalLength = $state[$index++];
        $this->channel3patternType = $state[$index++];
        $this->channel3frequency = $state[$index++];
        $this->channel3consecutive = $state[$index++];
        $this->channel3PCM = $state[$index++];
        $this->channel3adjustedFrequencyPrep = $state[$index++];
        $this->channel4adjustedFrequencyPrep = $state[$index++];
        $this->channel4lastSampleLookup = $state[$index++];
        $this->channel4totalLength = $state[$index++];
        $this->channel4envelopeVolume = $state[$index++];
        $this->channel4currentVolume = $state[$index++];
        $this->channel4envelopeType = $state[$index++];
        $this->channel4envelopeSweeps = $state[$index++];
        $this->channel4consecutive = $state[$index++];
        $this->channel4volumeEnvTime = $state[$index++];
        $this->channel4lastTotalLength = $state[$index++];
        $this->soundMasterEnabled = $state[$index++];
        $this->VinLeftChannelEnabled = $state[$index++];
        $this->VinRightChannelEnabled = $state[$index++];
        $this->VinLeftChannelMasterVolume = $state[$index++];
        $this->VinRightChannelMasterVolume = $state[$index++];
        $this->vinLeft = $state[$index++];
        $this->vinRight = $state[$index++];
        $this->leftChannel = $state[$index++];
        $this->rightChannel = $state[$index++];
        $this->actualScanLine = $state[$index++];
        $this->RTCisLatched = $state[$index++];
        $this->latchedSeconds = $state[$index++];
        $this->latchedMinutes = $state[$index++];
        $this->latchedHours = $state[$index++];
        $this->latchedLDays = $state[$index++];
        $this->latchedHDays = $state[$index++];
        $this->RTCSeconds = $state[$index++];
        $this->RTCMinutes = $state[$index++];
        $this->RTCHours = $state[$index++];
        $this->RTCDays = $state[$index++];
        $this->RTCDayOverFlow = $state[$index++];
        $this->RTCHALT = $state[$index++];
        $this->gbColorizedPalette = $state[$index++];
        $this->usedBootROM = $state[$index++];
        $this->skipPCIncrement = $state[$index++];
        $this->STATTracker = $state[$index++];
        $this->gbcRamBankPositionECHO = $state[$index++];
        $this->numRAMBanks = $state[$index];
        $this->tileCountInvalidator = $this->tileCount * 4;
        $this->fromSaveState = true;
        $this->checkPaletteType();
        $this->convertAuxilliary();
        $this->initializeLCDController();
        $this->memoryReadJumpCompile();
        $this->memoryWriteJumpCompile();
        $this->initLCD();
        $this->initSound();
        $this->drawToCanvas();
    }

    public function start()
    {
        Settings::$settings[4] = 0;    //Reset the frame skip setting.
        $this->initializeLCDController(); //Compile the LCD controller functions.
        $this->initMemory();  //Write the startup memory.
        $this->ROMLoad();     //Load the ROM into memory and get cartridge information from it.
        $this->initLCD();     //Initializae the graphics.
        $this->initSound();   //Sound object initialization.
        $this->run();         //Start the emulation.
    }

    public function convertAuxilliary()
    {
        try {
            // @TODO - Uint16
            // Its OK, tested
            $this->DAATable = $this->DAATable;
            $this->TICKTable = $this->TICKTable;
            $this->SecondaryTICKTable = $this->SecondaryTICKTable;
        } catch (\Exception $e) {
            echo 'Could not convert the auxilliary arrays to typed arrays' . PHP_EOL;
        }
    }

    public function initMemory() {
        //Initialize the RAM:
        $this->memory = $this->getTypedArray(0x10000, 0, 'uint8');
        $this->frameBuffer = $this->getTypedArray(23040, 0x00FFFFFF, 'int32');
        $this->gbPalette = $this->ArrayPad(12, 0);              //32-bit signed
        $this->gbColorizedPalette = $this->ArrayPad(12, 0);     //32-bit signed
        $this->gbcRawPalette = $this->ArrayPad(0x80, -1000);    //32-bit signed
        $this->gbcPalette = [0x40];                  //32-bit signed
        $this->convertAuxilliary();
        //Initialize the GBC Palette:
        $index = 0x3F;

        while ($index >= 0) {
            $this->gbcPalette[$index] = ($index < 0x20) ? -1 : 0;
            $index--;
        }
    }

    public function initSkipBootstrap() {
        //Start as an unset device:
        echo 'Starting without the GBC boot ROM' . PHP_EOL;

        $this->programCounter = 0x100;
        $this->stackPointer = 0xFFFE;
        $this->IME = true;
        $this->LCDTicks = 15;
        $this->DIVTicks = 14;
        $this->registerA = ($this->cGBC) ? 0x11 : 0x1;
        $this->registerB = 0;
        $this->registerC = 0x13;
        $this->registerD = 0;
        $this->registerE = 0xD8;
        $this->FZero = true;
        $this->FSubtract = false;
        $this->FHalfCarry = true;
        $this->FCarry = true;
        $this->registersHL = 0x014D;
        $this->leftChannel = [true, true, true, false];
        $this->rightChannel = [true, true, true, false];

        //Fill in the boot ROM set register values
        //Default values to the GB boot ROM values, then fill in the GBC boot ROM values after ROM loading
        $index = 0xFF;

        while ($index >= 0) {
            if ($index >= 0x30 && $index < 0x40) {
                $this->memoryWrite(0xFF00 + $index, $this->ffxxDump[$index]);
            } else {
                switch ($index) {
                    case 0x00:
                    case 0x01:
                    case 0x02:
                    case 0x07:
                    case 0x0F:
                    case 0x40:
                    case 0xFF:
                        $this->memoryWrite(0xFF00 + $index, $this->ffxxDump[$index]);
                        break;
                    default:
                        $this->memory[0xFF00 + $index] = $this->ffxxDump[$index];
                }
            }
            $index--;
        }
    }

    public function initBootstrap() {
        //Start as an unset device:
        echo 'Starting the GBC boot ROM.' . PHP_EOL;

        $this->programCounter = 0;
        $this->stackPointer = 0;
        $this->IME = false;
        $this->LCDTicks = 0;
        $this->DIVTicks = 0;
        $this->registerA = 0;
        $this->registerB = 0;
        $this->registerC = 0;
        $this->registerD = 0;
        $this->registerE = 0;
        $this->FZero = $this->FSubtract = $this->FHalfCarry = $this->FCarry = false;
        $this->registersHL = 0;
        $this->leftChannel = $this->ArrayPad(4, false);
        $this->rightChannel = $this->ArrayPad(4, false);
        $this->channel2frequency = $this->channel1frequency = 0;
        $this->channel2volumeEnvTime = $this->channel1volumeEnvTime = 0;
        $this->channel2consecutive = $this->channel1consecutive = true;
        $this->memory[0xFF00] = 0xF;  //Set the joypad state.
    }

    public function ROMLoad() {
        //Load the first two ROM banks (0x0000 - 0x7FFF) into regular gameboy memory:
        $this->ROM = $this->getTypedArray(strlen($this->ROMImage), 0, "uint8");

        $this->usedBootROM = Settings::$settings[16];

        for ($romIndex = 0; $romIndex < strlen($this->ROMImage); $romIndex++) {

            $this->ROM[$romIndex] = (ord($this->ROMImage[$romIndex]) & 0xFF);
            if ($romIndex < 0x8000) {
                if (!$this->usedBootROM || $romIndex >= 0x900 || ($romIndex >= 0x100 && $romIndex < 0x200)) {
                    $this->memory[$romIndex] = $this->ROM[$romIndex];     //Load in the game ROM.
                }
                else {
                    $this->memory[$romIndex] = $this->GBCBOOTROM[$romIndex];  //Load in the GameBoy Color BOOT ROM.
                }
            }
        }
        // ROM name
        for ($index = 0x134; $index < 0x13F; $index++) {
            if (ord($this->ROMImage[$index]) > 0) {
                $this->name .= $this->ROMImage[$index];
            }
        }

        // ROM game code (for newer games)
        for ($index = 0x13F; $index < 0x143; $index++) {
            if (ord($this->ROMImage[$index]) > 0) {
                $this->gameCode .= $this->ROMImage[$index];
            }
        }

        echo "Game Title: " . $this->name . "[" . $this->gameCode . "][" . $this->ROMImage[0x143] . "]" . PHP_EOL;

        echo "Game Code: " . $this->gameCode . PHP_EOL;

        // Cartridge type
        $this->cartridgeType = $this->ROM[0x147];
        echo "Cartridge type #" . $this->cartridgeType . PHP_EOL;

        //Map out ROM cartridge sub-types.
        $MBCType = "";

        switch ($this->cartridgeType) {
            case 0x00:
                //ROM w/o bank switching
                if (!Settings::$settings[9]) {
                    $MBCType = "ROM";
                    break;
                }
            case 0x01:
                $this->cMBC1 = true;
                $MBCType = "MBC1";
                break;
            case 0x02:
                $this->cMBC1 = true;
                $this->cSRAM = true;
                $MBCType = "MBC1 + SRAM";
                break;
            case 0x03:
                $this->cMBC1 = true;
                $this->cSRAM = true;
                $this->cBATT = true;
                $MBCType = "MBC1 + SRAM + BATT";
                break;
            case 0x05:
                $this->cMBC2 = true;
                $MBCType = "MBC2";
                break;
            case 0x06:
                $this->cMBC2 = true;
                $this->cBATT = true;
                $MBCType = "MBC2 + BATT";
                break;
            case 0x08:
                $this->cSRAM = true;
                $MBCType = "ROM + SRAM";
                break;
            case 0x09:
                $this->cSRAM = true;
                $this->cBATT = true;
                $MBCType = "ROM + SRAM + BATT";
                break;
            case 0x0B:
                $this->cMMMO1 = true;
                $MBCType = "MMMO1";
                break;
            case 0x0C:
                $this->cMMMO1 = true;
                $this->cSRAM = true;
                $MBCType = "MMMO1 + SRAM";
                break;
            case 0x0D:
                $this->cMMMO1 = true;
                $this->cSRAM = true;
                $this->cBATT = true;
                $MBCType = "MMMO1 + SRAM + BATT";
                break;
            case 0x0F:
                $this->cMBC3 = true;
                $this->cTIMER = true;
                $this->cBATT = true;
                $MBCType = "MBC3 + TIMER + BATT";
                break;
            case 0x10:
                $this->cMBC3 = true;
                $this->cTIMER = true;
                $this->cBATT = true;
                $this->cSRAM = true;
                $MBCType = "MBC3 + TIMER + BATT + SRAM";
                break;
            case 0x11:
                $this->cMBC3 = true;
                $MBCType = "MBC3";
                break;
            case 0x12:
                $this->cMBC3 = true;
                $this->cSRAM = true;
                $MBCType = "MBC3 + SRAM";
                break;
            case 0x13:
                $this->cMBC3 = true;
                $this->cSRAM = true;
                $this->cBATT = true;
                $MBCType = "MBC3 + SRAM + BATT";
                break;
            case 0x19:
                $this->cMBC5 = true;
                $MBCType = "MBC5";
                break;
            case 0x1A:
                $this->cMBC5 = true;
                $this->cSRAM = true;
                $MBCType = "MBC5 + SRAM";
                break;
            case 0x1B:
                $this->cMBC5 = true;
                $this->cSRAM = true;
                $this->cBATT = true;
                $MBCType = "MBC5 + SRAM + BATT";
                break;
            case 0x1C:
                $this->cRUMBLE = true;
                $MBCType = "RUMBLE";
                break;
            case 0x1D:
                $this->cRUMBLE = true;
                $this->cSRAM = true;
                $MBCType = "RUMBLE + SRAM";
                break;
            case 0x1E:
                $this->cRUMBLE = true;
                $this->cSRAM = true;
                $this->cBATT = true;
                $MBCType = "RUMBLE + SRAM + BATT";
                break;
            case 0x1F:
                $this->cCamera = true;
                $MBCType = "GameBoy Camera";
                break;
            case 0xFD:
                $this->cTAMA5 = true;
                $MBCType = "TAMA5";
                break;
            case 0xFE:
                $this->cHuC3 = true;
                $MBCType = "HuC3";
                break;
            case 0xFF:
                $this->cHuC1 = true;
                $MBCType = "HuC1";
                break;
            default:
                $MBCType = "Unknown";
                echo "Cartridge type is unknown." . PHP_EOL;

                // @TODO
                //pause();
        }

        echo "Cartridge Type: " . $MBCType . PHP_EOL;

        // ROM and RAM banks
        $this->numROMBanks = $this->ROMBanks[$this->ROM[0x148]];

        echo $this->numROMBanks . " ROM banks." . PHP_EOL;

        switch ($this->RAMBanks[$this->ROM[0x149]]) {
            case 0:
                echo "No RAM banking requested for allocation or MBC is of type 2." . PHP_EOL;
                break;
            case 2:
                echo "1 RAM bank requested for allocation." . PHP_EOL;
                break;
            case 3:
                echo "4 RAM banks requested for allocation." . PHP_EOL;
                break;
            case 4:
                echo "16 RAM banks requested for allocation." . PHP_EOL;
                break;
            default:
                echo "RAM bank amount requested is unknown, will use maximum allowed by specified MBC type." . PHP_EOL;
        }

        //Check the GB/GBC mode byte:
        if (!$this->usedBootROM) {
            switch ($this->ROM[0x143]) {
                case 0x00:  //Only GB mode
                    $this->cGBC = false;
                    echo "Only GB mode detected." . PHP_EOL;
                    break;
                case 0x80:  //Both GB + GBC modes
                    $this->cGBC = ! Settings::$settings[2];
                    echo "GB and GBC mode detected." . PHP_EOL;
                    break;
                case 0xC0:  //Only GBC mode
                    $this->cGBC = true;
                    echo "Only GBC mode detected." . PHP_EOL;
                    break;
                default:
                    $this->cGBC = false;
                    echo "Unknown GameBoy game type code #" . $this->ROM[0x143] . ", defaulting to GB mode (Old games don't have a type code)." . PHP_EOL;
            }

            $this->inBootstrap = false;
            $this->setupRAM();    //CPU/(V)RAM initialization.
            $this->initSkipBootstrap();
        }
        else {
            $this->cGBC = true;   //Allow the GBC boot ROM to run in GBC mode...
            $this->setupRAM();    //CPU/(V)RAM initialization.
            $this->initBootstrap();
        }
        $this->checkPaletteType();
        //License Code Lookup:
        $cOldLicense = $this->ROM[0x14B];
        $cNewLicense = ($this->ROM[0x144] & 0xFF00) | ($this->ROM[0x145] & 0xFF);
        if ($cOldLicense != 0x33) {
            //Old Style License Header
            echo "Old style license code: " . $cOldLicense . PHP_EOL;
        }
        else {
            //New Style License Header
            echo "New style license code: " . $cNewLicense . PHP_EOL;
        }
    }

    public function disableBootROM() {
        //Remove any traces of the boot ROM from ROM memory.
        for ($index = 0; $index < 0x900; $index++) {
            if ($index < 0x100 || $index >= 0x200) {      //Skip the already loaded in ROM header.
                $this->memory[$index] = $this->ROM[$index];   //Replace the GameBoy Color boot ROM with the game ROM.
            }
        }
        $this->checkPaletteType();

        if (!$this->cGBC) {
            //Clean up the post-boot (GB mode only) state:
            echo "Stepping down from GBC mode." . PHP_EOL;
            $this->tileCount /= 2;
            $this->tileCountInvalidator = $this->tileCount * 4;
            if (!Settings::$settings[17]) {
                $this->transparentCutoff = 4;
            }
            $this->colorCount = 12;

            // @TODO
            // $this->tileData.length = $this->tileCount * $this->colorCount;

            unset($this->VRAM);
            unset($this->GBCMemory);
            //Possible Extra: shorten some gfx arrays to the length that we need (Remove the unused indices)
        }

        $this->memoryReadJumpCompile();
        $this->memoryWriteJumpCompile();
    }

    public function setupRAM() {
        //Setup the auxilliary/switchable RAM to their maximum possible size (Bad headers can lie).
        if ($this->cMBC2) {
            $this->numRAMBanks = 1 / 16;
        }
        else if ($this->cMBC1 || $this->cRUMBLE || $this->cMBC3 || $this->cHuC3) {
            $this->numRAMBanks = 4;
        }
        else if ($this->cMBC5) {
            $this->numRAMBanks = 16;
        }
        else if ($this->cSRAM) {
            $this->numRAMBanks = 1;
        }
        if ($this->numRAMBanks > 0) {
            if (!$this->MBCRAMUtilized()) {
                //For ROM and unknown MBC cartridges using the external RAM:
                $this->MBCRAMBanksEnabled = true;
            }
            //Switched RAM Used
            $this->MBCRam = $this->getTypedArray($this->numRAMBanks * 0x2000, 0, "uint8");
        }
        echo "Actual bytes of MBC RAM allocated: " . ($this->numRAMBanks * 0x2000) . PHP_EOL;
        //Setup the RAM for GBC mode.
        if ($this->cGBC) {
            $this->VRAM = $this->getTypedArray(0x2000, 0, "uint8");
            $this->GBCMemory = $this->getTypedArray(0x7000, 0, "uint8");
            $this->tileCount *= 2;
            $this->tileCountInvalidator = $this->tileCount * 4;
            $this->colorCount = 64;
            $this->transparentCutoff = 32;
        }
        $this->tileData = $this->ArrayPad($this->tileCount * $this->colorCount, null);
        $this->tileReadState = $this->getTypedArray($this->tileCount, 0, "uint8");
        $this->memoryReadJumpCompile();
        $this->memoryWriteJumpCompile();
    }

    public function MBCRAMUtilized() {
        return $this->cMBC1 || $this->cMBC2 || $this->cMBC3 || $this->cMBC5 || $this->cRUMBLE;
    }

    public function initLCD() {
        $this->scaledFrameBuffer = $this->getTypedArray($this->pixelCount, 0, "int32");   //Used for software side scaling...
        $this->transparentCutoff = (Settings::$settings[17] || $this->cGBC) ? 32 : 4;
        if (count($this->weaveLookup) == 0) {
            //Setup the image decoding lookup table:
            $this->weaveLookup = $this->getTypedArray(256, 0, "uint16");
            for ($i_ = 0x1; $i_ <= 0xFF; $i_++) {
                for ($d_ = 0; $d_ < 0x8; $d_++) {
                    $this->weaveLookup[$i_] += (($i_ >> $d_) & 1) << ($d_ * 2);
                }
            }
        }
        try {
            if (Settings::$settings[5]) {
                //Nasty since we are throwing on purpose to force a try/catch fallback
                // throw(new Error(""));
                throw new \Exception("");
            }

            // @TODO
            //Create a white screen
            // $this->drawContext = $this->canvas.getContext("2d");
            // $this->drawContext->fillStyle = "rgb(255, 255, 255)";
            // $this->drawContext->fillRect(0, 0, $this->width, $this->height);

            $this->drawContext = new DrawContext();

            // NEW
            $this->width = 160;
            $this->height = 144;

            //Get a CanvasPixelArray buffer:
            try {
                //$this->canvasBuffer = $this->drawContext->createImageData($this->width, $this->height);
                $this->canvasBuffer = new \stdClass();
                $this->canvasBuffer->data = array_fill(0, 4 * $this->width * $this->height, 255);
            }
            catch (\Exception $error) {
                echo "Falling back to the getImageData initialization" . PHP_EOL;

                $this->canvasBuffer = $this->drawContext->getImageData(0, 0, $this->width, $this->height);
            }

            $index = $this->pixelCount;
            $index2 = $this->rgbCount;

            while ($index > 0) {
                $this->frameBuffer[--$index] = 0x00FFFFFF;
                $this->canvasBuffer->data[$index2 -= 4] = 0xFF;
                $this->canvasBuffer->data[$index2 + 1] = 0xFF;
                $this->canvasBuffer->data[$index2 + 2] = 0xFF;
                $this->canvasBuffer->data[$index2 + 3] = 0xFF;
            }

            $this->drawContext->putImageData($this->canvasBuffer, 0, 0);     //Throws any browser that won't support this later on.
            // $this->canvasAlt->style->visibility = "hidden"; //Make sure, if restarted, that the fallback images aren't going cover the canvas.
            // $this->canvas->style->visibility = "visible";
            $this->canvasFallbackHappened = false;
        } catch (\Exception $error) {
            //Falling back to an experimental data URI BMP file canvas alternative:
            echo "Falling back to BMP imaging as a canvas alternative." . PHP_EOL;

            $this->width = 160;
            $this->height = 144;
            $this->canvasFallbackHappened = true;
            $this->drawContext = new BMPCanvas($this->canvasAlt, 160, 144, Settings::$settings[6][0], Settings::$settings[6][1]);
            $this->canvasBuffer = new Object();

            $index = 23040;
            while ($index > 0) {
                $this->frameBuffer[--$index] = 0x00FFFFFF;
            }
            $this->canvasBuffer->data = $this->ArrayPad(92160, 0xFF);
            $this->drawContext->putImageData($this->canvasBuffer, 0, 0);
            //Make visible only after the images have been initialized.
            $this->canvasAlt->style->visibility = "visible";
            $this->canvas->style->visibility = "hidden";            //Speedier layout in some browsers.
        }
    }

    public function JoyPadEvent($key, $down)
    {
        if ($down) {
            $this->JoyPad &= 0xFF ^ (1 << $key);
            /*if (!$this->cGBC) {
                $this->memory[0xFF0F] |= 0x10;    //A real GBC doesn't set this!
            }*/
        } else {
            $this->JoyPad |= (1 << $key);
        }
        $this->memory[0xFF00] = ($this->memory[0xFF00] & 0x30) + (((($this->memory[0xFF00] & 0x20) == 0) ? ($this->JoyPad >> 4) : 0xF) & ((($this->memory[0xFF00] & 0x10) == 0) ? ($this->JoyPad & 0xF) : 0xF));
    }

    public function initSound() {
        // Not implemented in PHP
        return;
    }

    public function initAudioBuffer() {
        // Not implemented in PHP
        return;
    }

    public function playAudio() {
        // Not implemented in PHP
        return;
    }

    public function audioUpdate() {
        // Not implemented in PHP
        return;
    }

    public function initializeStartState() {
        // Not implemented in PHP
        return;
    }

    public function generateAudio() {
        // Not implemented in PHP
        return;
    }

    public function channel1Compute() {
        // Not implemented in PHP
        return;
    }

    public function channel2Compute() {
        // Not implemented in PHP
        return;
    }

    public function channel3Compute() {
        // Not implemented in PHP
        return;
    }

    public function channel4Compute() {
        // Not implemented in PHP
        return;
    }

    public function run() {
        //The preprocessing before the actual iteration loop:
        try {
            if (($this->stopEmulator & 2) == 0) {
                if (($this->stopEmulator & 1) == 1) {
                    $this->stopEmulator = 0;
                    $this->clockUpdate();         //Frame skip and RTC code.
                    // $this->audioUpdate();         //Lookup the rollover buffer and output WAVE PCM samples if sound is on and have fallen back to it.
                    if (!$this->halt) {           //If no HALT... Execute normally
                        $this->executeIteration();
                    }
                    else {                      //If we bailed out of a halt because the iteration ran down its timing.
                        $this->CPUTicks = 1;
                        $this->OPCODE[0x76]($this);
                        //Execute Interrupt:
                        $this->runInterrupt();
                        //Timing:
                        $this->updateCore();
                        $this->executeIteration();
                    }
                }
                else {      //We can only get here if there was an internal error, but the loop was restarted.
                    echo "Iterator restarted a faulted core." . PHP_EOL;
                    pause();
                }
            }
        } catch (\Exception $error) {
            if ($error->getMessage() != "HALT_OVERRUN") {
                echo 'GameBoy runtime error' . PHP_EOL;
            }
        }
    }

    public function executeIteration() {
        //Iterate the interpreter loop:
        $op = 0;

        while ($this->stopEmulator == 0) {
            //Fetch the current opcode.
            $op = $this->memoryRead($this->programCounter);
            if (!$this->skipPCIncrement) {
                //Increment the program counter to the next instruction:
                $this->programCounter = ($this->programCounter + 1) & 0xFFFF;
            }
            $this->skipPCIncrement = false;
            //Get how many CPU cycles the current op code counts for:
            $this->CPUTicks = $this->TICKTable[$op];
            //Execute the OP code instruction:
            $this->OPCODE[$op]($this);
            //Interrupt Arming:
            switch ($this->untilEnable) {
                case 1:
                    $this->IME = true;
                case 2:
                    $this->untilEnable--;
            }
            //Execute Interrupt:
            if ($this->IME) {
                $this->runInterrupt();
            }
            //Timing:
            $this->updateCore();
        }
    }

    public function runInterrupt() {
        $bitShift = 0;
        $testbit = 1;
        $interrupts = $this->memory[0xFFFF] & $this->memory[0xFF0F];

        while ($bitShift < 5) {
            //Check to see if an interrupt is enabled AND requested.
            if (($testbit & $interrupts) == $testbit) {
                $this->IME = false;                   //Reset the interrupt enabling.
                $this->memory[0xFF0F] -= $testbit;     //Reset the interrupt request.
                //Set the stack pointer to the current program counter value:
                $this->stackPointer = $this->unswtuw($this->stackPointer - 1);
                $this->memoryWrite($this->stackPointer, $this->programCounter >> 8);
                $this->stackPointer = $this->unswtuw($this->stackPointer - 1);
                $this->memoryWrite($this->stackPointer, $this->programCounter & 0xFF);
                //Set the program counter to the interrupt's address:
                $this->programCounter = 0x0040 + ($bitShift * 0x08);
                //Interrupts have a certain clock cycle length:
                $this->CPUTicks += 5; //People say it's around 5.
                break;  //We only want the highest priority interrupt.
            }

            $testbit = 1 << ++$bitShift;
        }
    }

    public function scanLineMode2() { // OAM in use
        if ($this->modeSTAT != 2) {
            if ($this->mode2TriggerSTAT) {
                $this->memory[0xFF0F] |= 0x2;// set IF bit 1
            }
            $this->STATTracker = 1;
            $this->modeSTAT = 2;
        }
    }

    public function scanLineMode3() { // OAM in use
        if ($this->modeSTAT != 3) {
            if ($this->mode2TriggerSTAT && $this->STATTracker == 0) {
                $this->memory[0xFF0F] |= 0x2;// set IF bit 1
            }
            $this->STATTracker = 1;
            $this->modeSTAT = 3;
        }
    }

    public function scanLineMode0() { // H-Blank
        if ($this->modeSTAT != 0) {
            if ($this->hdmaRunning && !$this->halt && $this->LCDisOn) {
                $this->performHdma(); //H-Blank DMA
            }
            if ($this->mode0TriggerSTAT || ($this->mode2TriggerSTAT && $this->STATTracker == 0)) {
                $this->memory[0xFF0F] |= 0x2; // if STAT bit 3 -> set IF bit1
            }
            $this->notifyScanline();
            $this->STATTracker = 2;
            $this->modeSTAT = 0;
        }
    }

    public function matchLYC() { // LY - LYC Compare
        if ($this->memory[0xFF44] == $this->memory[0xFF45]) { // If LY==LCY
            $this->memory[0xFF41] |= 0x04; // set STAT bit 2: LY-LYC coincidence flag
            if ($this->LYCMatchTriggerSTAT) {
                $this->memory[0xFF0F] |= 0x2; // set IF bit 1
            }
        }
        else {
            $this->memory[0xFF41] &= 0xFB; // reset STAT bit 2 (LY!=LYC)
        }
    }

    public function updateCore() {
        // DIV control
        $this->DIVTicks += $this->CPUTicks;
        if ($this->DIVTicks >= 0x40) {
            $this->DIVTicks -= 0x40;
            $this->memory[0xFF04] = ($this->memory[0xFF04] + 1) & 0xFF; // inc DIV
        }
        //LCD Controller Ticks
        $timedTicks = $this->CPUTicks / $this->multiplier;
        // LCD Timing
        $this->LCDTicks += $timedTicks;                //LCD timing
        $this->LCDCONTROL[$this->actualScanLine]($this); //Scan Line and STAT Mode Control
        //Audio Timing
        $this->audioTicks += $timedTicks;              //Not the same as the LCD timing (Cannot be altered by display on/off changes!!!).
        if ($this->audioTicks >= Settings::$settings[11]) {      //Are we past the granularity setting?
            $amount = $this->audioTicks * $this->samplesOut;
            $actual = floor($amount);
            $this->rollover += $amount - $actual;
            if ($this->rollover >= 1) {
                $this->rollover -= 1;
                $actual += 1;
            }
            if (!$this->audioOverflow && $actual > 0) {
                $this->generateAudio($actual);
            }
            //Emulator Timing (Timed against audio for optimization):
            $this->emulatorTicks += $this->audioTicks;
            if ($this->emulatorTicks >= Settings::$settings[13]) {
                if (($this->stopEmulator & 1) == 0) { //Make sure we don't overdo the audio.
                    $this->playAudio();               //Output all the samples built up.
                    if ($this->drewBlank == 0) {      //LCD off takes at least 2 frames.
                        $this->drawToCanvas();        //Display frame
                    }
                }
                $this->stopEmulator |= 1;             //End current loop.
                $this->emulatorTicks = 0;
            }
            $this->audioTicks = 0;
        }
        // Internal Timer
        if ($this->TIMAEnabled) {
            $this->timerTicks += $this->CPUTicks;
            while ($this->timerTicks >= $this->TACClocker) {
                $this->timerTicks -= $this->TACClocker;
                if ($this->memory[0xFF05] == 0xFF) {
                    $this->memory[0xFF05] = $this->memory[0xFF06];
                    $this->memory[0xFF0F] |= 0x4; // set IF bit 2
                }
                else {
                    $this->memory[0xFF05]++;
                }
            }
        }
    }

    public function initializeLCDController() {
        //Display on hanlding:
        $line = 0;

        while ($line < 154) {
            if ($line < 143) {
                //We're on a normal scan line:
                $this->LINECONTROL[$line] = function ($parentObj) {
                    if ($parentObj->LCDTicks < 20) {
                        $parentObj->scanLineMode2();  // mode2: 80 cycles
                    }
                    else if ($parentObj->LCDTicks < 63) {
                        $parentObj->scanLineMode3();  // mode3: 172 cycles
                    }
                    else if ($parentObj->LCDTicks < 114) {
                        $parentObj->scanLineMode0();  // mode0: 204 cycles
                    }
                    else {
                        //We're on a new scan line:
                        $parentObj->LCDTicks -= 114;
                        $parentObj->actualScanLine = ++$parentObj->memory[0xFF44];
                        $parentObj->matchLYC();
                        if ($parentObj->STATTracker != 2) {
                            if ($parentObj->hdmaRunning && !$parentObj->halt && $parentObj->LCDisOn) {
                                $parentObj->performHdma();    //H-Blank DMA
                            }
                            if ($parentObj->mode0TriggerSTAT) {
                                $parentObj->memory[0xFF0F] |= 0x2;// set IF bit 1
                            }
                        }
                        $parentObj->STATTracker = 0;
                        $parentObj->scanLineMode2();  // mode2: 80 cycles
                        if ($parentObj->LCDTicks >= 114) {
                            //We need to skip 1 or more scan lines:
                            $parentObj->notifyScanline();
                            $parentObj->LCDCONTROL[$parentObj->actualScanLine]($parentObj);  //Scan Line and STAT Mode Control
                        }
                    }
                };
            } else if ($line == 143) {
                //We're on the last visible scan line of the LCD screen:
                $this->LINECONTROL[143] = function ($parentObj) {
                    if ($parentObj->LCDTicks < 20) {
                        $parentObj->scanLineMode2();  // mode2: 80 cycles
                    }
                    else if ($parentObj->LCDTicks < 63) {
                        $parentObj->scanLineMode3();  // mode3: 172 cycles
                    }
                    else if ($parentObj->LCDTicks < 114) {
                        $parentObj->scanLineMode0();  // mode0: 204 cycles
                    }
                    else {
                        //Starting V-Blank:
                        //Just finished the last visible scan line:
                        $parentObj->LCDTicks -= 114;
                        $parentObj->actualScanLine = ++$parentObj->memory[0xFF44];
                        $parentObj->matchLYC();
                        if ($parentObj->mode1TriggerSTAT) {
                            $parentObj->memory[0xFF0F] |= 0x2;// set IF bit 1
                        }
                        if ($parentObj->STATTracker != 2) {
                            if ($parentObj->hdmaRunning && !$parentObj->halt && $parentObj->LCDisOn) {
                                $parentObj->performHdma();    //H-Blank DMA
                            }
                            if ($parentObj->mode0TriggerSTAT) {
                                $parentObj->memory[0xFF0F] |= 0x2;// set IF bit 1
                            }
                        }
                        $parentObj->STATTracker = 0;
                        $parentObj->modeSTAT = 1;
                        $parentObj->memory[0xFF0F] |= 0x1;    // set IF flag 0
                        if ($parentObj->drewBlank > 0) {      //LCD off takes at least 2 frames.
                            $parentObj->drewBlank--;
                        }
                        if ($parentObj->LCDTicks >= 114) {
                            //We need to skip 1 or more scan lines:
                            $parentObj->LCDCONTROL[$parentObj->actualScanLine]($parentObj);  //Scan Line and STAT Mode Control
                        }
                    }
                };
            } else if ($line < 153) {
                //In VBlank
                $this->LINECONTROL[$line] = function ($parentObj) {
                    if ($parentObj->LCDTicks >= 114) {
                        //We're on a new scan line:
                        $parentObj->LCDTicks -= 114;
                        $parentObj->actualScanLine = ++$parentObj->memory[0xFF44];
                        $parentObj->matchLYC();
                        if ($parentObj->LCDTicks >= 114) {
                            //We need to skip 1 or more scan lines:
                            $parentObj->LCDCONTROL[$parentObj->actualScanLine]($parentObj);  //Scan Line and STAT Mode Control
                        }
                    }
                };
            }
            else {
                //VBlank Ending (We're on the last actual scan line)
                $this->LINECONTROL[153] = function ($parentObj) {
                    if ($parentObj->memory[0xFF44] == 153) {
                        $parentObj->memory[0xFF44] = 0;   //LY register resets to 0 early.
                        $parentObj->matchLYC();           //LY==LYC Test is early here (Fixes specific one-line glitches (example: Kirby2 intro)).
                    }
                    if ($parentObj->LCDTicks >= 114) {
                        //We reset back to the beginning:
                        $parentObj->LCDTicks -= 114;
                        $parentObj->actualScanLine = 0;
                        $parentObj->scanLineMode2();  // mode2: 80 cycles
                        if ($parentObj->LCDTicks >= 114) {
                            //We need to skip 1 or more scan lines:
                            $parentObj->LCDCONTROL[$parentObj->actualScanLine]($parentObj);  //Scan Line and STAT Mode Control
                        }
                    }
                };
            }
            $line++;
        }
        $this->LCDCONTROL = ($this->LCDisOn) ? $this->LINECONTROL : $this->DISPLAYOFFCONTROL;
    }

    public function DisplayShowOff() {
        if ($this->drewBlank == 0) {
            //Draw a blank screen:
            try {
                // @TODO
                // $this->drawContext->fillStyle = "white";
                $this->drawContext->fillRect(0, 0, $this->width, $this->height);
            } catch (\Exception $e) {
                //cout("Could not use fillStyle / fillRect.", 2);
                $index = $this->pixelCount;

                while ($index > 0) {
                    $this->canvasBuffer->data[--$index] = 0xFF;
                }

                $this->drawContext->putImageData($this->canvasBuffer, 0, 0);
            }
            $this->drewBlank = 2;
        }
    }

    public function performHdma() {
        $this->CPUTicks += 1 + (8 * $this->multiplier);

        $dmaSrc = ($this->memory[0xFF51] << 8) + $this->memory[0xFF52];
        $dmaDstRelative = ($this->memory[0xFF53] << 8) + $this->memory[0xFF54];
        $dmaDstFinal = $dmaDstRelative + 0x10;
        $tileRelative = $this->tileData->length - $this->tileCount;

        if ($this->currVRAMBank == 1) {
            while ($dmaDstRelative < $dmaDstFinal) {
                if ($dmaDstRelative < 0x1800) {      // Bkg Tile data area
                    $tileIndex = ($dmaDstRelative >> 4) + 384;
                    if ($this->tileReadState[$tileIndex] == 1) {
                        $r = $tileRelative + $tileIndex;
                        do {
                            $this->tileData[$r] = null;
                            $r -= $this->tileCount;
                        } while ($r >= 0);
                        $this->tileReadState[$tileIndex] = 0;
                    }
                }
                $this->VRAM[$dmaDstRelative++] = $this->memoryRead($dmaSrc++);
            }
        } else {
            while ($dmaDstRelative < $dmaDstFinal) {
                if ($dmaDstRelative < 0x1800) {      // Bkg Tile data area
                    $tileIndex = $dmaDstRelative >> 4;
                    if ($this->tileReadState[$tileIndex] == 1) {
                        $r = $tileRelative + $tileIndex;

                        do {
                            $this->tileData[$r] = null;
                            $r -= $this->tileCount;
                        } while ($r >= 0);

                        $this->tileReadState[$tileIndex] = 0;
                    }
                }
                $this->memory[0x8000 + $dmaDstRelative++] = $this->memoryRead($dmaSrc++);
            }
        }

        $this->memory[0xFF51] = (($dmaSrc & 0xFF00) >> 8);
        $this->memory[0xFF52] = ($dmaSrc & 0x00F0);
        $this->memory[0xFF53] = (($dmaDstFinal & 0x1F00) >> 8);
        $this->memory[0xFF54] = ($dmaDstFinal & 0x00F0);
        if ($this->memory[0xFF55] == 0) {
            $this->hdmaRunning = false;
            $this->memory[0xFF55] = 0xFF; //Transfer completed ("Hidden last step," since some ROMs don't imply this, but most do).
        }
        else {
            $this->memory[0xFF55]--;
        }
    }

    public function clockUpdate() {
        //We're tying in the same timer for RTC and frame skipping, since we can and this reduces load.
        if (Settings::$settings[7] || $this->cTIMER) {
            $timeElapsed = microtime(true) - $this->lastIteration;    //Get the numnber of milliseconds since this last executed.
            if ($this->cTIMER && !$this->RTCHALT) {
                //Update the MBC3 RTC:
                $this->RTCSeconds += $timeElapsed / 1000;
                while ($this->RTCSeconds >= 60) { //System can stutter, so the seconds difference can get large, thus the "while".
                    $this->RTCSeconds -= 60;
                    $this->RTCMinutes++;
                    if ($this->RTCMinutes >= 60) {
                        $this->RTCMinutes -= 60;
                        $this->RTCHours++;
                        if ($this->RTCHours >= 24) {
                            $this->RTCHours -= 24;
                            $this->RTCDays++;
                            if ($this->RTCDays >= 512) {
                                $this->RTCDays -= 512;
                                $this->RTCDayOverFlow = true;
                            }
                        }
                    }
                }
            }
            if (Settings::$settings[7]) {
                //Auto Frame Skip:
                if ($timeElapsed > Settings::$settings[20]) {
                    //Did not finish in time...
                    if (Settings::$settings[4] < Settings::$settings[8]) {
                        Settings::$settings[4]++;
                    }
                }
                else if (Settings::$settings[4] > 0) {
                    //We finished on time, decrease frame skipping (throttle to somewhere just below full speed)...
                    Settings::$settings[4]--;
                }
            }
            $this->lastIteration = microtime();
        }
    }

    public function drawToCanvas() {
        //Draw the frame buffer to the canvas:
        if (Settings::$settings[4] == 0 || $this->frameCount > 0) {
            //Copy and convert the framebuffer data to the CanvasPixelArray format.
            $canvasData = $this->canvasBuffer->data;
            $frameBuffer = (Settings::$settings[21] && $this->pixelCount > 0 && $this->width != 160 && $this->height != 144) ? $this->resizeFrameBuffer() : $this->frameBuffer;
            $bufferIndex = $this->pixelCount;
            $canvasIndex = $this->rgbCount;

            while ($canvasIndex > 3) {
                $canvasData[$canvasIndex -= 4] = ($frameBuffer[--$bufferIndex] >> 16) & 0xFF;       //Red
                $canvasData[$canvasIndex + 1] = ($frameBuffer[$bufferIndex] >> 8) & 0xFF;           //Green
                $canvasData[$canvasIndex + 2] = $frameBuffer[$bufferIndex] & 0xFF;                  //Blue
            }

            $this->canvasBuffer->data = $canvasData;

            // @TODO
            //Draw out the CanvasPixelArray data:
            $this->drawContext->putImageData($this->canvasBuffer, 0, 0);

            if (Settings::$settings[4] > 0) {
                //Increment the frameskip counter:
                $this->frameCount -= Settings::$settings[4];
            }
        }
        else {
            //Reset the frameskip counter:
            $this->frameCount += Settings::$settings[12];
        }
    }

    public function resizeFrameBuffer() {
        //Attempt to resize the canvas in software instead of in CSS:
        $column = 0;
        $rowOffset = 0;
        for ($row = 0; $row < $this->height; $row++) {
            $rowOffset = floor($row * $this->heightRatio) * 160;
            for ($column = 0; $column < $this->width; $column++) {
                $this->scaledFrameBuffer[($row * $this->width) + $column] = $this->frameBuffer[$rowOffset + floor($column * $this->widthRatio)];
            }
        }
        return $this->scaledFrameBuffer;
    }

    public function invalidateAll($pal) {
        $stop = ($pal + 1) * $this->tileCountInvalidator;
        for ($r = $pal * $this->tileCountInvalidator; $r < $stop; $r++) {
            $this->tileData[$r] = null;
        }
    }

    public function setGBCPalettePre($index_, $data) {
        if ($this->gbcRawPalette[$index_] == $data) {
            return;
        }
        $this->gbcRawPalette[$index_] = $data;
        if ($index_ >= 0x40 && ($index_ & 0x6) == 0) {
            // stay transparent
            return;
        }
        $value = ($this->gbcRawPalette[$index_ | 1] << 8) + $this->gbcRawPalette[$index_ & -2];
        $this->gbcPalette[$index_ >> 1] = 0x80000000 + (($value & 0x1F) << 19) + (($value & 0x3E0) << 6) + (($value & 0x7C00) >> 7);
        $this->invalidateAll($index_ >> 3);
    }

    public function setGBCPalette($index_, $data) {
        $this->setGBCPalettePre($index_, $data);
        if (($index_ & 0x6) == 0) {
            $this->gbcPalette[$index_ >> 1] &= 0x00FFFFFF;
        }
    }

    public function decodePalette($startIndex, $data) {
        if (!$this->cGBC) {
            $this->gbPalette[$startIndex] = $this->colors[$data & 0x03] & 0x00FFFFFF; // color 0: transparent
            $this->gbPalette[$startIndex + 1] = $this->colors[($data >> 2) & 0x03];
            $this->gbPalette[$startIndex + 2] = $this->colors[($data >> 4) & 0x03];
            $this->gbPalette[$startIndex + 3] = $this->colors[$data >> 6];

            if ($this->usedBootROM) { //Do palette conversions if we did the GBC bootup:
                //GB colorization:
                $startOffset = ($startIndex >= 4) ? 0x20 : 0;
                $pal2 = $this->gbcPalette[$startOffset + (($data >> 2) & 0x03)];
                $pal3 = $this->gbcPalette[$startOffset + (($data >> 4) & 0x03)];
                $pal4 = $this->gbcPalette[$startOffset + ($data >> 6)];
                $this->gbColorizedPalette[$startIndex] = $this->gbcPalette[$startOffset + ($data & 0x03)] & 0x00FFFFFF;
                $this->gbColorizedPalette[$startIndex + 1] = ($pal2 >= 0x80000000) ? $pal2 : 0xFFFFFFFF;
                $this->gbColorizedPalette[$startIndex + 2] = ($pal3 >= 0x80000000) ? $pal3 : 0xFFFFFFFF;
                $this->gbColorizedPalette[$startIndex + 3] = ($pal4 >= 0x80000000) ? $pal4 : 0xFFFFFFFF;
            }

            //@TODO - Need to copy the new palette
            $this->checkPaletteType();
        }
    }

    public function notifyScanline() {
        if ($this->actualScanLine == 0) {
            $this->windowSourceLine = 0;
        }
        // determine the left edge of the window (160 if window is inactive)
        $windowLeft = ($this->gfxWindowDisplay && $this->memory[0xFF4A] <= $this->actualScanLine) ? min(160, $this->memory[0xFF4B] - 7) : 160;
        // step 1: background+window
        $skippedAnything = $this->drawBackgroundForLine($this->actualScanLine, $windowLeft, 0);
        // At this point, the high (alpha) byte in the frameBuffer is 0xff for colors 1,2,3 and
        // 0x00 for color 0. Foreground sprites draw on all colors, background sprites draw on
        // top of color 0 only.
        // step 2: sprites
        $this->drawSpritesForLine($this->actualScanLine);
        // step 3: prio tiles+window
        if ($skippedAnything) {
            $this->drawBackgroundForLine($this->actualScanLine, $windowLeft, 0x80);
        }
        if ($windowLeft < 160) {
            $this->windowSourceLine++;
        }
    }

    public function drawBackgroundForLine($line, $windowLeft, $priority) {
        $skippedTile = false;
        $tileNum = 0;
        $tileXCoord = 0;
        $tileAttrib = 0;
        $sourceY = $line + $this->memory[0xFF42];
        $sourceImageLine = $sourceY & 0x7;
        $tileX = $this->memory[0xFF43] >> 3;
        $memStart = (($this->gfxBackgroundY) ? 0x1C00 : 0x1800) + (($sourceY & 0xF8) << 2);
        $screenX = -($this->memory[0xFF43] & 7);

        for (; $screenX < $windowLeft; $tileX++, $screenX += 8) {
            $tileXCoord = ($tileX & 0x1F);
            $baseaddr = $this->memory[0x8000 + $memStart + $tileXCoord];
            $tileNum = ($this->gfxBackgroundX) ? $baseaddr : (($baseaddr > 0x7F) ? (($baseaddr & 0x7F) + 0x80) : ($baseaddr + 0x100));
            if ($this->cGBC) {
                $mapAttrib = $this->VRAM[$memStart + $tileXCoord];
                if (($mapAttrib & 0x80) != $priority) {
                    $skippedTile = true;
                    continue;
                }
                $tileAttrib = (($mapAttrib & 0x07) << 2) + (($mapAttrib >> 5) & 0x03);
                $tileNum += 384 * (($mapAttrib >> 3) & 0x01); // tile vram bank
            }
            $this->drawPartCopy($tileNum, $screenX, $line, $sourceImageLine, $tileAttrib);
        }

        if ($windowLeft < 160) {
            // window!
            $windowStartAddress = ($this->gfxWindowY) ? 0x1C00 : 0x1800;
            $windowSourceTileY = $this->windowSourceLine >> 3;
            $tileAddress = $windowStartAddress + ($windowSourceTileY * 0x20);
            $windowSourceTileLine = $this->windowSourceLine & 0x7;
            for ($screenX = $windowLeft; $screenX < 160; $tileAddress++, $screenX += 8) {
                $baseaddr = $this->memory[0x8000 + $tileAddress];
                $tileNum = ($this->gfxBackgroundX) ? $baseaddr : (($baseaddr > 0x7F) ? (($baseaddr & 0x7F) + 0x80) : ($baseaddr + 0x100));
                if ($this->cGBC) {
                    $mapAttrib = $this->VRAM[$tileAddress];
                    if (($mapAttrib & 0x80) != $priority) {
                        $skippedTile = true;
                        continue;
                    }
                    $tileAttrib = (($mapAttrib & 0x07) << 2) + (($mapAttrib >> 5) & 0x03); // mirroring
                    $tileNum += 384 * (($mapAttrib >> 3) & 0x01); // tile vram bank
                }
                $this->drawPartCopy($tileNum, $screenX, $line, $windowSourceTileLine, $tileAttrib);
            }
        }
        return $skippedTile;
    }

    public function drawPartCopy($tileIndex, $x, $y, $sourceLine, $attribs) {
        $image = $this->tileData[$tileIndex + $this->tileCount * $attribs] ? $this->tileData[$tileIndex + $this->tileCount * $attribs] : $this->updateImage($tileIndex, $attribs);
        $dst = $x + $y * 160;
        $src = $sourceLine * 8;
        $dstEnd = ($x > 152) ? (($y + 1) * 160) : ($dst + 8);
        if ($x < 0) { // adjust left
            $dst -= $x;
            $src -= $x;
        }

        while ($dst < $dstEnd) {
            $this->frameBuffer[$dst++] = $image[$src++];
        }
    }

    public function checkPaletteType() {
        //Reference the correct palette ahead of time...
        $this->palette = ($this->cGBC) ? $this->gbcPalette : (($this->usedBootROM && Settings::$settings[17]) ? $this->gbColorizedPalette : $this->gbPalette);
    }

    public function updateImage($tileIndex, $attribs) {
        $index_ = $tileIndex + $this->tileCount * $attribs;
        $otherBank = ($tileIndex >= 384);
        $offset = $otherBank ? (($tileIndex - 384) << 4) : ($tileIndex << 4);
        $paletteStart = $attribs & 0xFC;
        $transparent = $attribs >= $this->transparentCutoff;
        $pixix = 0;
        $pixixdx = 1;
        $pixixdy = 0;
        $tempPix = $this->getTypedArray(64, 0, "int32");
        if (($attribs & 2) != 0) {
            $pixixdy = -16;
            $pixix = 56;
        }
        if (($attribs & 1) == 0) {
            $pixixdx = -1;
            $pixix += 7;
            $pixixdy += 16;
        }
        for ($y = 8; --$y >= 0;) {
            $num = $this->weaveLookup[$this->VRAMReadGFX($offset++, $otherBank)] + ($this->weaveLookup[$this->VRAMReadGFX($offset++, $otherBank)] << 1);
            if ($num != 0) {
                $transparent = false;
            }
            for ($x = 8; --$x >= 0;) {
                $tempPix[$pixix] = $this->palette[$paletteStart + ($num & 3)] & -1;
                $pixix += $pixixdx;
                $num  >>= 2;
            }
            $pixix += $pixixdy;
        }
        $this->tileData[$index_] = ($transparent) ? true : $tempPix;

        $this->tileReadState[$tileIndex] = 1;
        return $this->tileData[$index_];
    }

    public function drawSpritesForLine($line) {
        if (!$this->gfxSpriteShow) {
            return;
        }
        $minSpriteY = $line - (($this->gfxSpriteDouble) ? 15 : 7);
        // either only do priorityFlag == 0 (all foreground),
        // or first 0x80 (background) and then 0 (foreground)
        $priorityFlag = $this->spritePriorityEnabled ? 0x80 : 0;
        for (; $priorityFlag >= 0; $priorityFlag -= 0x80) {
            $oamIx = 159;
            while ($oamIx >= 0) {
                $attributes = 0xFF & $this->memory[0xFE00 + $oamIx--];
                if (($attributes & 0x80) == $priorityFlag || !$this->spritePriorityEnabled) {
                    $tileNum = (0xFF & $this->memory[0xFE00 + $oamIx--]);
                    $spriteX = (0xFF & $this->memory[0xFE00 + $oamIx--]) - 8;
                    $spriteY = (0xFF & $this->memory[0xFE00 + $oamIx--]) - 16;
                    $offset = $line - $spriteY;
                    if ($spriteX >= 160 || $spriteY < $minSpriteY || $offset < 0) {
                        continue;
                    }
                    if ($this->gfxSpriteDouble) {
                        $tileNum = $tileNum & 0xFE;
                    }
                    $spriteAttrib = ($attributes >> 5) & 0x03; // flipx: from bit 0x20 to 0x01, flipy: from bit 0x40 to 0x02
                    if ($this->cGBC) {
                        $spriteAttrib += 0x20 + (($attributes & 0x07) << 2); // palette
                        $tileNum += (384 >> 3) * ($attributes & 0x08); // tile vram bank
                    }
                    else {
                        // attributes 0x10: 0x00 = OBJ1 palette, 0x10 = OBJ2 palette
                        // spriteAttrib: 0x04: OBJ1 palette, 0x08: OBJ2 palette
                        $spriteAttrib += 0x4 + (($attributes & 0x10) >> 2);
                    }
                    if ($priorityFlag == 0x80) {
                    // background
                        if ($this->gfxSpriteDouble) {
                            if (($spriteAttrib & 2) != 0) {
                                $this->drawPartBgSprite(($tileNum | 1) - ($offset >> 3), $spriteX, $line, $offset & 7, $spriteAttrib);
                            }
                            else {
                                $this->drawPartBgSprite(($tileNum & -2) + ($offset >> 3), $spriteX, $line, $offset & 7, $spriteAttrib);
                            }
                        }
                        else {
                            $this->drawPartBgSprite($tileNum, $spriteX, $line, $offset, $spriteAttrib);
                        }
                    }
                    else {
                        // foreground
                        if ($this->gfxSpriteDouble) {
                            if (($spriteAttrib & 2) != 0) {
                                $this->drawPartFgSprite(($tileNum | 1) - ($offset >> 3), $spriteX, $line, $offset & 7, $spriteAttrib);
                            }
                            else {
                                $this->drawPartFgSprite(($tileNum & -2) + ($offset >> 3), $spriteX, $line, $offset & 7, $spriteAttrib);
                            }
                        }
                        else {
                            $this->drawPartFgSprite($tileNum, $spriteX, $line, $offset, $spriteAttrib);
                        }
                    }
                }
                else {
                    $oamIx -= 3;
                }
            }
        }
    }

    public function drawPartFgSprite($tileIndex, $x, $y, $sourceLine, $attribs) {
        $im = $this->tileData[$tileIndex + $this->tileCount * $attribs] ? $this->tileData[$tileIndex + $this->tileCount * $attribs] : $this->updateImage($tileIndex, $attribs);
        if ($im === true) {
            return;
        }
        $dst = $x + $y * 160;
        $src = $sourceLine * 8;
        $dstEnd = ($x > 152) ? (($y + 1) * 160) : ($dst + 8);
        if ($x < 0) { // adjust left
            $dst -= $x;
            $src -= $x;
        }

        while ($dst < $dstEnd) {
            $this->frameBuffer[$dst] = $im[$src];
            $dst++;
            $src++;
        }
    }

    public function drawPartBgSprite($tileIndex, $x, $y, $sourceLine, $attribs) {
        $im = $this->tileData[$tileIndex + $this->tileCount * $attribs] ? $this->tileData[$tileIndex + $this->tileCount * $attribs] : $this->updateImage($tileIndex, $attribs);
        if ($im === true) {
            return;
        }
        $dst = $x + $y * 160;
        $src = $sourceLine * 8;
        $dstEnd = ($x > 152) ? (($y + 1) * 160) : ($dst + 8);
        if ($x < 0) { // adjust left
            $dst -= $x;
            $src -= $x;
        }
        while ($dst < $dstEnd) {
            //if ($im[$src] < 0 && $this->frameBuffer[$dst] >= 0) {
                $this->frameBuffer[$dst] = $im[$src];
            // }
            $dst++;
            $src++;
        }
    }

    //Memory Reading:
    public function memoryRead($address) {
        //Act as a wrapper for reading the returns from the compiled jumps to memory.
        return $this->memoryReader[$address]($this, $address);   //This seems to be faster than the usual if/else.
    }

    public function memoryReadJumpCompile() {
        //Faster in some browsers, since we are doing less conditionals overall by implementing them in advance.
        for ($index = 0x0000; $index <= 0xFFFF; $index++) {
            if ($index < 0x4000) {
                $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadNormal
                    return $parentObj->memory[$address];
                };
            }
            else if ($index < 0x8000) {
                $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadROM
                    return $parentObj->ROM[$parentObj->currentROMBank + $address];
                };
            }
            else if ($index >= 0x8000 && $index < 0xA000) {
                $VRAMReadCGBCPU = function ($parentObj, $address) {
                    //CPU Side Reading The VRAM (Optimized for GameBoy Color)
                    return ($parentObj->modeSTAT > 2) ? 0xFF : (($parentObj->currVRAMBank == 0) ? $parentObj->memory[$address] : $parentObj->VRAM[$address - 0x8000]);
                };

                $VRAMReadDMGCPU = function ($parentObj, $address) {
                    //CPU Side Reading The VRAM (Optimized for classic GameBoy)
                    return ($parentObj->modeSTAT > 2) ? 0xFF : $parentObj->memory[$address];
                };

                $this->memoryReader[$index] = ($this->cGBC) ? $VRAMReadCGBCPU : $VRAMReadDMGCPU;
            }
            else if ($index >= 0xA000 && $index < 0xC000) {
                if (($this->numRAMBanks == 1 / 16 && $index < 0xA200) || $this->numRAMBanks >= 1) {
                    if (!$this->cMBC3) {
                        $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadMBC
                            //Switchable RAM
                            if ($parentObj->MBCRAMBanksEnabled || Settings::$settings[10]) {
                                return $parentObj->MBCRam[$address + $parentObj->currMBCRAMBankPosition];
                            }
                            //cout("Reading from disabled RAM.", 1);
                            return 0xFF;
                        };
                    }
                    else {
                        //MBC3 RTC + RAM:
                        $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadMBC3
                            //Switchable RAM
                            if ($parentObj->MBCRAMBanksEnabled || Settings::$settings[10]) {
                                switch ($parentObj->currMBCRAMBank) {
                                    case 0x00:
                                    case 0x01:
                                    case 0x02:
                                    case 0x03:
                                        return $parentObj->MBCRam[$address + $parentObj->currMBCRAMBankPosition];
                                        break;
                                    case 0x08:
                                        return $parentObj->latchedSeconds;
                                        break;
                                    case 0x09:
                                        return $parentObj->latchedMinutes;
                                        break;
                                    case 0x0A:
                                        return $parentObj->latchedHours;
                                        break;
                                    case 0x0B:
                                        return $parentObj->latchedLDays;
                                        break;
                                    case 0x0C:
                                        return ((($parentObj->RTCDayOverFlow) ? 0x80 : 0) + (($parentObj->RTCHALT) ? 0x40 : 0)) + $parentObj->latchedHDays;
                                }
                            }
                            //cout("Reading from invalid or disabled RAM.", 1);
                            return 0xFF;
                        };
                    }
                }
                else {
                    $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadBAD
                        return 0xFF;
                    };
                }
            }
            else if ($index >= 0xC000 && $index < 0xE000) {
                if (!$this->cGBC || $index < 0xD000) {
                    $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadNormal
                        return $parentObj->memory[$address];
                    };
                }
                else {
                    $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadGBCMemory
                        return $parentObj->GBCMemory[$address + $parentObj->gbcRamBankPosition];
                    };
                }
            }
            else if ($index >= 0xE000 && $index < 0xFE00) {
                if (!$this->cGBC || $index < 0xF000) {
                    $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadECHONormal
                        return $parentObj->memory[$address - 0x2000];
                    };
                }
                else {
                    $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadECHOGBCMemory
                        return $parentObj->GBCMemory[$address + $parentObj->gbcRamBankPositionECHO];
                    };
                }
            }
            else if ($index < 0xFEA0) {
                $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadOAM
                    return ($parentObj->modeSTAT > 1) ?  0xFF : $parentObj->memory[$address];
                };
            }
            else if ($this->cGBC && $index >= 0xFEA0 && $index < 0xFF00) {
                $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadNormal
                    return $parentObj->memory[$address];
                };
            }
            else if ($index >= 0xFF00) {
                switch ($index) {
                    case 0xFF00:
                        $this->memoryReader[0xFF00] = function ($parentObj, $address) {
                            return 0xC0 | $parentObj->memory[0xFF00]; //Top nibble returns as set.
                        };
                        break;
                    case 0xFF01:
                        $this->memoryReader[0xFF01] = function ($parentObj, $address) {
                            return (($parentObj->memory[0xFF02] & 0x1) == 0x1) ? 0xFF : $parentObj->memory[0xFF01];
                        };
                        break;
                    case 0xFF02:
                        if ($this->cGBC) {
                            $this->memoryReader[0xFF02] = function ($parentObj, $address) {
                                return 0x7C | $parentObj->memory[0xFF02];
                            };
                        }
                        else {
                            $this->memoryReader[0xFF02] = function ($parentObj, $address) {
                                return 0x7E | $parentObj->memory[0xFF02];
                            };
                        }
                        break;
                    case 0xFF07:
                        $this->memoryReader[0xFF07] = function ($parentObj, $address) {
                            return 0xF8 | $parentObj->memory[0xFF07];
                        };
                        break;
                    case 0xFF0F:
                        $this->memoryReader[0xFF0F] = function ($parentObj, $address) {
                            return 0xE0 | $parentObj->memory[0xFF0F];
                        };
                        break;
                    case 0xFF10:
                        $this->memoryReader[0xFF10] = function ($parentObj, $address) {
                            return 0x80 | $parentObj->memory[0xFF10];
                        };
                        break;
                    case 0xFF11:
                        $this->memoryReader[0xFF11] = function ($parentObj, $address) {
                            return 0x3F | $parentObj->memory[0xFF11];
                        };
                        break;
                    case 0xFF14:
                        $this->memoryReader[0xFF14] = function ($parentObj, $address) {
                            return 0xBF | $parentObj->memory[0xFF14];
                        };
                        break;
                    case 0xFF16:
                        $this->memoryReader[0xFF16] = function ($parentObj, $address) {
                            return 0x3F | $parentObj->memory[0xFF16];
                        };
                        break;
                    case 0xFF19:
                        $this->memoryReader[0xFF19] = function ($parentObj, $address) {
                            return 0xBF | $parentObj->memory[0xFF19];
                        };
                        break;
                    case 0xFF1A:
                        $this->memoryReader[0xFF1A] = function ($parentObj, $address) {
                            return 0x7F | $parentObj->memory[0xFF1A];
                        };
                        break;
                    case 0xFF1B:
                        $this->memoryReader[0xFF1B] = function ($parentObj, $address) {
                            return 0xFF;
                        };
                        break;
                    case 0xFF1C:
                        $this->memoryReader[0xFF1C] = function ($parentObj, $address) {
                            return 0x9F | $parentObj->memory[0xFF1C];
                        };
                        break;
                    case 0xFF1E:
                        $this->memoryReader[0xFF1E] = function ($parentObj, $address) {
                            return 0xBF | $parentObj->memory[0xFF1E];
                        };
                        break;
                    case 0xFF20:
                        $this->memoryReader[0xFF20] = function ($parentObj, $address) {
                            return 0xFF;
                        };
                        break;
                    case 0xFF23:
                        $this->memoryReader[0xFF23] = function ($parentObj, $address) {
                            return 0xBF | $parentObj->memory[0xFF23];
                        };
                        break;
                    case 0xFF26:
                        $this->memoryReader[0xFF26] = function ($parentObj, $address) {
                            return 0x70 | $parentObj->memory[0xFF26];
                        };
                        break;
                    case 0xFF30:
                    case 0xFF31:
                    case 0xFF32:
                    case 0xFF33:
                    case 0xFF34:
                    case 0xFF35:
                    case 0xFF36:
                    case 0xFF37:
                    case 0xFF38:
                    case 0xFF39:
                    case 0xFF3A:
                    case 0xFF3B:
                    case 0xFF3C:
                    case 0xFF3D:
                    case 0xFF3E:
                    case 0xFF3F:
                        $this->memoryReader[$index] = function ($parentObj, $address) {
                            return (($parentObj->memory[0xFF26] & 0x4) == 0x4) ? 0xFF : $parentObj->memory[$address];
                        };
                        break;
                    case 0xFF41:
                        $this->memoryReader[0xFF41] = function ($parentObj, $address) {
                            return 0x80 | $parentObj->memory[0xFF41] | $parentObj->modeSTAT;
                        };
                        break;
                    case 0xFF44:
                        $this->memoryReader[0xFF44] = function ($parentObj, $address) {
                            return (($parentObj->LCDisOn) ? $parentObj->memory[0xFF44] : 0);
                        };
                        break;
                    case 0xFF4F:
                        $this->memoryReader[0xFF4F] = function ($parentObj, $address) {
                            return $parentObj->currVRAMBank;
                        };
                        break;
                    default:
                        $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadNormal
                            return $parentObj->memory[$address];
                        };
                }
            }
            else {
                $this->memoryReader[$index] = function ($parentObj, $address) { //memoryReadBAD
                    return 0xFF;
                };
            }
        }
    }

    public function VRAMReadGFX($address, $gbcBank) {
        //Graphics Side Reading The VRAM
        return ((!$gbcBank) ? $this->memory[0x8000 + $address] : $this->VRAM[$address]);
    }

    public function setCurrentMBC1ROMBank() {
        //Read the cartridge ROM data from RAM memory:
        switch ($this->ROMBank1offs) {
            case 0x00:
            case 0x20:
            case 0x40:
            case 0x60:
                //Bank calls for 0x00, 0x20, 0x40, and 0x60 are really for 0x01, 0x21, 0x41, and 0x61.
                $this->currentROMBank = $this->ROMBank1offs * 0x4000;
                break;
            default:
                $this->currentROMBank = ($this->ROMBank1offs - 1) * 0x4000;
        }
        while ($this->currentROMBank + 0x4000 >= count($this->ROM)) {
            $this->currentROMBank -= count($this->ROM);
        }
    }

    public function setCurrentMBC2AND3ROMBank() {
        //Read the cartridge ROM data from RAM memory:
        //Only map bank 0 to bank 1 here (MBC2 is like MBC1, but can only do 16 banks, so only the bank 0 quirk appears for MBC2):
        $this->currentROMBank = max($this->ROMBank1offs - 1, 0) * 0x4000;
        while ($this->currentROMBank + 0x4000 >= count($this->ROM)) {
            $this->currentROMBank -= count($this->ROM);
        }
    }
    public function setCurrentMBC5ROMBank() {
        //Read the cartridge ROM data from RAM memory:
        $this->currentROMBank = ($this->ROMBank1offs - 1) * 0x4000;
        while ($this->currentROMBank + 0x4000 >= count($this->ROM)) {
            $this->currentROMBank -= count($this->ROM);
        }
    }

    //Memory Writing:
    public function memoryWrite($address, $data) {
        //Act as a wrapper for writing by compiled jumps to specific memory writing functions.
        $this->memoryWriter[$address]($this, $address, $data);
    }

    public function memoryWriteJumpCompile() {
        $MBCWriteEnable = function ($parentObj, $address, $data) {
            //MBC RAM Bank Enable/Disable:
            $parentObj->MBCRAMBanksEnabled = (($data & 0x0F) == 0x0A); //If lower nibble is 0x0A, then enable, otherwise disable.
        };

        $MBC3WriteROMBank = function ($parentObj, $address, $data) {
            //MBC3 ROM bank switching:
            $parentObj->ROMBank1offs = $data & 0x7F;
            $parentObj->setCurrentMBC2AND3ROMBank();
        };

        $cartIgnoreWrite = function ($parentObj, $address, $data) {
            //We might have encountered illegal RAM writing or such, so just do nothing...
        };

        //Faster in some browsers, since we are doing less conditionals overall by implementing them in advance.
        for ($index = 0x0000; $index <= 0xFFFF; $index++) {
            if ($index < 0x8000) {
                if ($this->cMBC1) {
                    if ($index < 0x2000) {
                        $this->memoryWriter[$index] = $MBCWriteEnable;
                    }
                    else if ($index < 0x4000) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { // MBC1WriteROMBank
                            //MBC1 ROM bank switching:
                            $parentObj->ROMBank1offs = ($parentObj->ROMBank1offs & 0x60) | ($data & 0x1F);
                            $parentObj->setCurrentMBC1ROMBank();
                        };
                    }
                    else if ($index < 0x6000) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //MBC1WriteRAMBank
                            //MBC1 RAM bank switching
                            if ($parentObj->MBC1Mode) {
                                //4/32 Mode
                                $parentObj->currMBCRAMBank = $data & 0x3;
                                $parentObj->currMBCRAMBankPosition = ($parentObj->currMBCRAMBank << 13) - 0xA000;
                            }
                            else {
                                //16/8 Mode
                                $parentObj->ROMBank1offs = (($data & 0x03) << 5) | ($parentObj->ROMBank1offs & 0x1F);
                                $parentObj->setCurrentMBC1ROMBank();
                            }
                        };
                    }
                    else {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //MBC1WriteType
                            //MBC1 mode setting:
                            $parentObj->MBC1Mode = (($data & 0x1) == 0x1);
                        };
                    }
                }
                else if ($this->cMBC2) {
                    if ($index < 0x1000) {
                        $this->memoryWriter[$index] = $MBCWriteEnable;
                    }
                    else if ($index >= 0x2100 && $index < 0x2200) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //MBC2WriteROMBank
                            //MBC2 ROM bank switching:
                            $parentObj->ROMBank1offs = $data & 0x0F;
                            $parentObj->setCurrentMBC2AND3ROMBank();
                        };
                    }
                    else {
                        $this->memoryWriter[$index] = $cartIgnoreWrite;
                    }
                }
                else if ($this->cMBC3) {
                    if ($index < 0x2000) {
                        $this->memoryWriter[$index] = $MBCWriteEnable;
                    }
                    else if ($index < 0x4000) {
                        $this->memoryWriter[$index] = $MBC3WriteROMBank;
                    }
                    else if ($index < 0x6000) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //MBC3WriteRAMBank
                            $parentObj->currMBCRAMBank = $data;
                            if ($data < 4) {
                                //MBC3 RAM bank switching
                                $parentObj->currMBCRAMBankPosition = ($parentObj->currMBCRAMBank << 13) - 0xA000;
                            }
                        };
                    }
                    else {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //MBC3WriteRTCLatch
                            if ($data == 0) {
                                $parentObj->RTCisLatched = false;
                            }
                            else if (!$parentObj->RTCisLatched) {
                                //Copy over the current RTC time for reading.
                                $parentObj->RTCisLatched = true;
                                $parentObj->latchedSeconds = floor($parentObj->RTCSeconds);
                                $parentObj->latchedMinutes = $parentObj->RTCMinutes;
                                $parentObj->latchedHours = $parentObj->RTCHours;
                                $parentObj->latchedLDays = ($parentObj->RTCDays & 0xFF);
                                $parentObj->latchedHDays = $parentObj->RTCDays >> 8;
                            }
                        };
                    }
                }
                else if ($this->cMBC5 || $this->cRUMBLE) {
                    if ($index < 0x2000) {
                        $this->memoryWriter[$index] = $MBCWriteEnable;
                    }
                    else if ($index < 0x3000) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //MBC5WriteROMBankLow
                            //MBC5 ROM bank switching:
                            $parentObj->ROMBank1offs = ($parentObj->ROMBank1offs & 0x100) | $data;
                            $parentObj->setCurrentMBC5ROMBank();
                        };
                    }
                    else if ($index < 0x4000) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //MBC5WriteROMBankHigh
                            //MBC5 ROM bank switching (by least significant bit):
                            $parentObj->ROMBank1offs  = (($data & 0x01) << 8) | ($parentObj->ROMBank1offs & 0xFF);
                            $parentObj->setCurrentMBC5ROMBank();
                        };
                    }
                    else if ($index < 0x6000) {
                        $RUMBLEWriteRAMBank = function ($parentObj, $address, $data) {
                            //MBC5 RAM bank switching
                            //Like MBC5, but bit 3 of the lower nibble is used for rumbling and bit 2 is ignored.
                            $parentObj->currMBCRAMBank = $data & 0x3;
                            $parentObj->currMBCRAMBankPosition = ($parentObj->currMBCRAMBank << 13) - 0xA000;
                        };

                        $MBC5WriteRAMBank = function ($parentObj, $address, $data) {
                            //MBC5 RAM bank switching
                            $parentObj->currMBCRAMBank = $data & 0xF;
                            $parentObj->currMBCRAMBankPosition = ($parentObj->currMBCRAMBank << 13) - 0xA000;
                        };

                        $this->memoryWriter[$index] = ($this->cRUMBLE) ? $RUMBLEWriteRAMBank : $MBC5WriteRAMBank;
                    }
                    else {
                        $this->memoryWriter[$index] = $cartIgnoreWrite;
                    }
                }
                else if ($this->cHuC3) {
                    if ($index < 0x2000) {
                        $this->memoryWriter[$index] = $MBCWriteEnable;
                    }
                    else if ($index < 0x4000) {
                        $this->memoryWriter[$index] = $MBC3WriteROMBank;
                    }
                    else if ($index < 0x6000) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //HuC3WriteRAMBank
                            //HuC3 RAM bank switching
                            $parentObj->currMBCRAMBank = $data & 0x03;
                            $parentObj->currMBCRAMBankPosition = ($parentObj->currMBCRAMBank << 13) - 0xA000;
                        };
                    }
                    else {
                        $this->memoryWriter[$index] = $cartIgnoreWrite;
                    }
                }
                else {
                    $this->memoryWriter[$index] = $cartIgnoreWrite;
                }
            }
            else if ($index < 0xA000) {
                $this->memoryWriter[$index] = function ($parentObj, $address, $data) { // VRAMWrite
                    if ($parentObj->modeSTAT < 3) {   //VRAM cannot be written to during mode 3
                        if ($address < 0x9800) {     // Bkg Tile data area
                            $tileIndex = (($address - 0x8000) >> 4) + (384 * $parentObj->currVRAMBank);
                            if ($parentObj->tileReadState[$tileIndex] == 1) {
                                $r = count($parentObj->tileData) - $parentObj->tileCount + $tileIndex;
                                do {
                                    $parentObj->tileData[$r] = null;
                                    $r -= $parentObj->tileCount;
                                } while ($r >= 0);
                                $parentObj->tileReadState[$tileIndex] = 0;
                            }
                        }
                        if ($parentObj->currVRAMBank == 0) {
                            $parentObj->memory[$address] = $data;
                        }
                        else {
                            $parentObj->VRAM[$address - 0x8000] = $data;
                        }
                    }
                };
            }
            else if ($index < 0xC000) {
                if (($this->numRAMBanks == 1 / 16 && $index < 0xA200) || $this->numRAMBanks >= 1) {
                    if (!$this->cMBC3) {
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteMBCRAM
                            if ($parentObj->MBCRAMBanksEnabled || Settings::$settings[10]) {
                                $parentObj->MBCRam[$address + $parentObj->currMBCRAMBankPosition] = $data;
                            }
                        };
                    }
                    else {
                        //MBC3 RTC + RAM:
                        $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteMBC3RAM
                            if ($parentObj->MBCRAMBanksEnabled || Settings::$settings[10]) {
                                switch ($parentObj->currMBCRAMBank) {
                                    case 0x00:
                                    case 0x01:
                                    case 0x02:
                                    case 0x03:
                                        $parentObj->MBCRam[$address + $parentObj->currMBCRAMBankPosition] = $data;
                                        break;
                                    case 0x08:
                                        if ($data < 60) {
                                            $parentObj->RTCSeconds = $data;
                                        }
                                        else {
                                            echo "(Bank #" + $parentObj->currMBCRAMBank + ") RTC write out of range: " + $data . PHP_EOL;
                                        }
                                        break;
                                    case 0x09:
                                        if ($data < 60) {
                                            $parentObj->RTCMinutes = $data;
                                        }
                                        else {
                                            echo "(Bank #" + $parentObj->currMBCRAMBank + ") RTC write out of range: " + $data . PHP_EOL;
                                        }
                                        break;
                                    case 0x0A:
                                        if ($data < 24) {
                                            $parentObj->RTCHours = $data;
                                        }
                                        else {
                                            echo "(Bank #" + $parentObj->currMBCRAMBank + ") RTC write out of range: " + $data . PHP_EOL;
                                        }
                                        break;
                                    case 0x0B:
                                        $parentObj->RTCDays = ($data & 0xFF) | ($parentObj->RTCDays & 0x100);
                                        break;
                                    case 0x0C:
                                        $parentObj->RTCDayOverFlow = ($data & 0x80) == 0x80;
                                        $parentObj->RTCHalt = ($data & 0x40) == 0x40;
                                        $parentObj->RTCDays = (($data & 0x1) << 8) | ($parentObj->RTCDays & 0xFF);
                                        break;
                                    default:
                                        echo "Invalid MBC3 bank address selected: " + $parentObj->currMBCRAMBank . PHP_EOL;
                                }
                            }
                        };
                    }
                }
                else {
                    $this->memoryWriter[$index] = $cartIgnoreWrite;
                }
            }
            else if ($index < 0xE000) {
                if ($this->cGBC && $index >= 0xD000) {
                    $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteGBCRAM
                        $parentObj->GBCMemory[$address + $parentObj->gbcRamBankPosition] = $data;
                    };
                }
                else {
                    $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteNormal
                        $parentObj->memory[$address] = $data;
                    };
                }
            }
            else if ($index < 0xFE00) {
                if ($this->cGBC && $index >= 0xF000) {
                    $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteECHOGBCRAM
                        $parentObj->GBCMemory[$address + $parentObj->gbcRamBankPositionECHO] = $data;
                    };
                }
                else {
                    $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteECHONormal
                        $parentObj->memory[$address - 0x2000] = $data;
                    };
                }
            }
            else if ($index <= 0xFEA0) {
                $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteOAMRAM
                    if ($parentObj->modeSTAT < 2) {       //OAM RAM cannot be written to in mode 2 & 3
                        $parentObj->memory[$address] = $data;
                    }
                };
            }
            else if ($index < 0xFF00) {
                if ($this->cGBC) {                                            //Only GBC has access to this RAM.
                    $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteNormal
                        $parentObj->memory[$address] = $data;
                    };
                }
                else {
                    $this->memoryWriter[$index] = $cartIgnoreWrite;
                }
            }
            else {
                //Start the I/O initialization by filling in the slots as normal memory:
                $this->memoryWriter[$index] = function ($parentObj, $address, $data) { //memoryWriteNormal
                    $parentObj->memory[$address] = $data;
                };
            }
        }
        $this->registerWriteJumpCompile();                //Compile the I/O write functions separately...
    }

    public function registerWriteJumpCompile() {
        //I/O Registers (GB + GBC):
        $this->memoryWriter[0xFF00] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF00] = ($data & 0x30) | (((($data & 0x20) == 0) ? ($parentObj->JoyPad >> 4) : 0xF) & ((($data & 0x10) == 0) ? ($parentObj->JoyPad & 0xF) : 0xF));
        };
        $this->memoryWriter[0xFF02] = function ($parentObj, $address, $data) {
            if ((($data & 0x1) == 0x1)) {
                //Internal clock:
                $parentObj->memory[0xFF02] = ($data & 0x7F);
                $parentObj->memory[0xFF0F] |= 0x8;    //Get this time delayed...
            }
            else {
                //External clock:
                $parentObj->memory[0xFF02] = $data;
                //No connected serial device, so don't trigger interrupt...
            }
        };
        $this->memoryWriter[0xFF04] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF04] = 0;
        };
        $this->memoryWriter[0xFF07] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF07] = $data & 0x07;
            $parentObj->TIMAEnabled = ($data & 0x04) == 0x04;
            $parentObj->TACClocker = pow(4, (($data & 0x3) != 0) ? ($data & 0x3) : 4); //TODO: Find a way to not make a conditional in here...
        };
        $this->memoryWriter[0xFF10] = function ($parentObj, $address, $data) {
            $parentObj->channel1lastTimeSweep = $parentObj->channel1timeSweep = floor((($data & 0x70) >> 4) * $parentObj->channel1TimeSweepPreMultiplier);
            $parentObj->channel1numSweep = $data & 0x07;
            $parentObj->channel1frequencySweepDivider = 1 << $parentObj->channel1numSweep;
            $parentObj->channel1decreaseSweep = (($data & 0x08) == 0x08);
            $parentObj->memory[0xFF10] = $data;
        };
        $this->memoryWriter[0xFF11] = function ($parentObj, $address, $data) {
            $parentObj->channel1duty = $data >> 6;
            $parentObj->channel1adjustedDuty = $parentObj->dutyLookup[$parentObj->channel1duty];
            $parentObj->channel1lastTotalLength = $parentObj->channel1totalLength = (0x40 - ($data & 0x3F)) * $parentObj->audioTotalLengthMultiplier;
            $parentObj->memory[0xFF11] = $data & 0xC0;
        };
        $this->memoryWriter[0xFF12] = function ($parentObj, $address, $data) {
            $parentObj->channel1envelopeVolume = $data >> 4;
            $parentObj->channel1currentVolume = $parentObj->channel1envelopeVolume / 0xF;
            $parentObj->channel1envelopeType = (($data & 0x08) == 0x08);
            $parentObj->channel1envelopeSweeps = $data & 0x7;
            $parentObj->channel1volumeEnvTime = $parentObj->channel1envelopeSweeps * $parentObj->volumeEnvelopePreMultiplier;
            $parentObj->memory[0xFF12] = $data;
        };
        $this->memoryWriter[0xFF13] = function ($parentObj, $address, $data) {
            $parentObj->channel1frequency = ($parentObj->channel1frequency & 0x700) | $data;
            //Pre-calculate the frequency computation outside the waveform generator for speed:
            $parentObj->channel1adjustedFrequencyPrep = $parentObj->preChewedAudioComputationMultiplier / (0x800 - $parentObj->channel1frequency);
            $parentObj->memory[0xFF13] = $data;
        };
        $this->memoryWriter[0xFF14] = function ($parentObj, $address, $data) {
            if (($data & 0x80) == 0x80) {
                $parentObj->channel1envelopeVolume = $parentObj->memory[0xFF12] >> 4;
                $parentObj->channel1currentVolume = $parentObj->channel1envelopeVolume / 0xF;
                $parentObj->channel1envelopeSweeps = $parentObj->memory[0xFF12] & 0x7;
                $parentObj->channel1volumeEnvTime = $parentObj->channel1envelopeSweeps * $parentObj->volumeEnvelopePreMultiplier;
                $parentObj->channel1totalLength = $parentObj->channel1lastTotalLength;
                $parentObj->channel1timeSweep = $parentObj->channel1lastTimeSweep;
                $parentObj->channel1numSweep = $parentObj->memory[0xFF10] & 0x07;
                $parentObj->channel1frequencySweepDivider = 1 << $parentObj->channel1numSweep;
                if (($data & 0x40) == 0x40) {
                    $parentObj->memory[0xFF26] |= 0x1;
                }
            }
            $parentObj->channel1consecutive = (($data & 0x40) == 0x0);
            $parentObj->channel1frequency = (($data & 0x7) << 8) | ($parentObj->channel1frequency & 0xFF);
            //Pre-calculate the frequency computation outside the waveform generator for speed:
            $parentObj->channel1adjustedFrequencyPrep = $parentObj->preChewedAudioComputationMultiplier / (0x800 - $parentObj->channel1frequency);
            $parentObj->memory[0xFF14] = $data & 0x40;
        };
        $this->memoryWriter[0xFF16] = function ($parentObj, $address, $data) {
            $parentObj->channel2duty = $data >> 6;
            $parentObj->channel2adjustedDuty = $parentObj->dutyLookup[$parentObj->channel2duty];
            $parentObj->channel2lastTotalLength = $parentObj->channel2totalLength = (0x40 - ($data & 0x3F)) * $parentObj->audioTotalLengthMultiplier;
            $parentObj->memory[0xFF16] = $data & 0xC0;
        };
        $this->memoryWriter[0xFF17] = function ($parentObj, $address, $data) {
            $parentObj->channel2envelopeVolume = $data >> 4;
            $parentObj->channel2currentVolume = $parentObj->channel2envelopeVolume / 0xF;
            $parentObj->channel2envelopeType = (($data & 0x08) == 0x08);
            $parentObj->channel2envelopeSweeps = $data & 0x7;
            $parentObj->channel2volumeEnvTime = $parentObj->channel2envelopeSweeps * $parentObj->volumeEnvelopePreMultiplier;
            $parentObj->memory[0xFF17] = $data;
        };
        $this->memoryWriter[0xFF18] = function ($parentObj, $address, $data) {
            $parentObj->channel2frequency = ($parentObj->channel2frequency & 0x700) | $data;
            //Pre-calculate the frequency computation outside the waveform generator for speed:
            $parentObj->channel2adjustedFrequencyPrep = $parentObj->preChewedAudioComputationMultiplier / (0x800 - $parentObj->channel2frequency);
            $parentObj->memory[0xFF18] = $data;
        };
        $this->memoryWriter[0xFF19] = function ($parentObj, $address, $data) {
            if (($data & 0x80) == 0x80) {
                $parentObj->channel2envelopeVolume = $parentObj->memory[0xFF17] >> 4;
                $parentObj->channel2currentVolume = $parentObj->channel2envelopeVolume / 0xF;
                $parentObj->channel2envelopeSweeps = $parentObj->memory[0xFF17] & 0x7;
                $parentObj->channel2volumeEnvTime = $parentObj->channel2envelopeSweeps * $parentObj->volumeEnvelopePreMultiplier;
                $parentObj->channel2totalLength = $parentObj->channel2lastTotalLength;
                if (($data & 0x40) == 0x40) {
                    $parentObj->memory[0xFF26] |= 0x2;
                }
            }
            $parentObj->channel2consecutive = (($data & 0x40) == 0x0);
            $parentObj->channel2frequency = (($data & 0x7) << 8) | ($parentObj->channel2frequency & 0xFF);
            //Pre-calculate the frequency computation outside the waveform generator for speed:
            $parentObj->channel2adjustedFrequencyPrep = $parentObj->preChewedAudioComputationMultiplier / (0x800 - $parentObj->channel2frequency);
            $parentObj->memory[0xFF19] = $data & 0x40;
        };
        $this->memoryWriter[0xFF1A] = function ($parentObj, $address, $data) {
            $parentObj->channel3canPlay = ($data >= 0x80);
            if ($parentObj->channel3canPlay && ($parentObj->memory[0xFF1A] & 0x80) == 0x80) {
                $parentObj->channel3totalLength = $parentObj->channel3lastTotalLength;
                if (!$parentObj->channel3consecutive) {
                    $parentObj->memory[0xFF26] |= 0x4;
                }
            }
            $parentObj->memory[0xFF1A] = $data & 0x80;
        };
        $this->memoryWriter[0xFF1B] = function ($parentObj, $address, $data) {
            $parentObj->channel3lastTotalLength = $parentObj->channel3totalLength = (0x100 - $data) * $parentObj->audioTotalLengthMultiplier;
            $parentObj->memory[0xFF1B] = $data;
        };
        $this->memoryWriter[0xFF1C] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF1C] = $data & 0x60;
            $parentObj->channel3patternType = $parentObj->memory[0xFF1C] >> 5;
        };
        $this->memoryWriter[0xFF1D] = function ($parentObj, $address, $data) {
            $parentObj->channel3frequency = ($parentObj->channel3frequency & 0x700) | $data;
            $parentObj->channel3adjustedFrequencyPrep = $parentObj->preChewedWAVEAudioComputationMultiplier / (0x800 - $parentObj->channel3frequency);
            $parentObj->memory[0xFF1D] = $data;
        };
        $this->memoryWriter[0xFF1E] = function ($parentObj, $address, $data) {
            if (($data & 0x80) == 0x80) {
                $parentObj->channel3totalLength = $parentObj->channel3lastTotalLength;
                if (($data & 0x40) == 0x40) {
                    $parentObj->memory[0xFF26] |= 0x4;
                }
            }
            $parentObj->channel3consecutive = (($data & 0x40) == 0x0);
            $parentObj->channel3frequency = (($data & 0x7) << 8) | ($parentObj->channel3frequency & 0xFF);
            $parentObj->channel3adjustedFrequencyPrep = $parentObj->preChewedWAVEAudioComputationMultiplier / (0x800 - $parentObj->channel3frequency);
            $parentObj->memory[0xFF1E] = $data & 0x40;
        };
        $this->memoryWriter[0xFF20] = function ($parentObj, $address, $data) {
            $parentObj->channel4lastTotalLength = $parentObj->channel4totalLength = (0x40 - ($data & 0x3F)) * $parentObj->audioTotalLengthMultiplier;
            $parentObj->memory[0xFF20] = $data | 0xC0;
        };
        $this->memoryWriter[0xFF21] = function ($parentObj, $address, $data) {
            $parentObj->channel4envelopeVolume = $data >> 4;
            $parentObj->channel4currentVolume = $parentObj->channel4envelopeVolume / 0xF;
            $parentObj->channel4envelopeType = (($data & 0x08) == 0x08);
            $parentObj->channel4envelopeSweeps = $data & 0x7;
            $parentObj->channel4volumeEnvTime = $parentObj->channel4envelopeSweeps * $parentObj->volumeEnvelopePreMultiplier;
            $parentObj->memory[0xFF21] = $data;
        };
        $this->memoryWriter[0xFF22] = function ($parentObj, $address, $data) {
            $parentObj->channel4lastSampleLookup = 0;
            $parentObj->channel4adjustedFrequencyPrep = $parentObj->whiteNoiseFrequencyPreMultiplier / max($data & 0x7, 0.5) / pow(2, ($data >> 4) + 1);
            $parentObj->noiseTableLookup = (($data & 0x8) == 0x8) ? $parentObj->smallNoiseTable : $parentObj->largeNoiseTable;
            $parentObj->memory[0xFF22] = $data;
        };
        $this->memoryWriter[0xFF23] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF23] = $data;
            $parentObj->channel4consecutive = (($data & 0x40) == 0x0);
            if (($data & 0x80) == 0x80) {
                $parentObj->channel4lastSampleLookup = 0;
                $parentObj->channel4envelopeVolume = $parentObj->memory[0xFF21] >> 4;
                $parentObj->channel4currentVolume = $parentObj->channel4envelopeVolume / 0xF;
                $parentObj->channel4envelopeSweeps = $parentObj->memory[0xFF21] & 0x7;
                $parentObj->channel4volumeEnvTime = $parentObj->channel4envelopeSweeps * $parentObj->volumeEnvelopePreMultiplier;
                $parentObj->channel4totalLength = $parentObj->channel4lastTotalLength;
                if (($data & 0x40) == 0x40) {
                    $parentObj->memory[0xFF26] |= 0x8;
                }
            }
        };
        $this->memoryWriter[0xFF24] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF24] = $data;
            /*$parentObj->VinLeftChannelEnabled = (($data >> 7) == 0x1);
            $parentObj->VinRightChannelEnabled = ((($data >> 3) & 0x1) == 0x1);
            $parentObj->VinLeftChannelMasterVolume = (($data >> 4) & 0x07);
            $parentObj->VinRightChannelMasterVolume = ($data & 0x07);
            $parentObj->vinLeft = ($parentObj->VinLeftChannelEnabled) ? $parentObj->VinLeftChannelMasterVolume / 7 : 1;
            $parentObj->vinRight = ($parentObj->VinRightChannelEnabled) ? $parentObj->VinRightChannelMasterVolume / 7 : 1;*/
        };
        $this->memoryWriter[0xFF25] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF25] = $data;
            $parentObj->leftChannel = [($data & 0x01) == 0x01, ($data & 0x02) == 0x02, ($data & 0x04) == 0x04, ($data & 0x08) == 0x08];
            $parentObj->rightChannel = [($data & 0x10) == 0x10, ($data & 0x20) == 0x20, ($data & 0x40) == 0x40, ($data & 0x80) == 0x80];
        };
        $this->memoryWriter[0xFF26] = function ($parentObj, $address, $data) {
            $soundEnabled = ($data & 0x80);
            $parentObj->memory[0xFF26] = $soundEnabled | ($parentObj->memory[0xFF26] & 0xF);
            $parentObj->soundMasterEnabled = ($soundEnabled == 0x80);
            if (!$parentObj->soundMasterEnabled) {
                $parentObj->memory[0xFF26] = 0;
                $parentObj->initializeStartState();
                for ($address = 0xFF30; $address < 0xFF40; $address++) {
                    $parentObj->memory[$address] = 0;
                }
            }
        };
        $this->memoryWriter[0xFF30] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[0] = $data >> 4;
            $parentObj->channel3PCM[1] = $data & 0xF;
            $parentObj->memory[0xFF30] = $data;
        };
        $this->memoryWriter[0xFF31] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[2] = $data >> 4;
            $parentObj->channel3PCM[3] = $data & 0xF;
            $parentObj->memory[0xFF31] = $data;
        };
        $this->memoryWriter[0xFF32] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[4] = $data >> 4;
            $parentObj->channel3PCM[5] = $data & 0xF;
            $parentObj->memory[0xFF32] = $data;
        };
        $this->memoryWriter[0xFF33] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[6] = $data >> 4;
            $parentObj->channel3PCM[7] = $data & 0xF;
            $parentObj->memory[0xFF33] = $data;
        };
        $this->memoryWriter[0xFF34] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[8] = $data >> 4;
            $parentObj->channel3PCM[9] = $data & 0xF;
            $parentObj->memory[0xFF34] = $data;
        };
        $this->memoryWriter[0xFF35] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[10] = $data >> 4;
            $parentObj->channel3PCM[11] = $data & 0xF;
            $parentObj->memory[0xFF35] = $data;
        };
        $this->memoryWriter[0xFF36] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[12] = $data >> 4;
            $parentObj->channel3PCM[13] = $data & 0xF;
            $parentObj->memory[0xFF36] = $data;
        };
        $this->memoryWriter[0xFF37] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[14] = $data >> 4;
            $parentObj->channel3PCM[15] = $data & 0xF;
            $parentObj->memory[0xFF37] = $data;
        };
        $this->memoryWriter[0xFF38] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[16] = $data >> 4;
            $parentObj->channel3PCM[17] = $data & 0xF;
            $parentObj->memory[0xFF38] = $data;
        };
        $this->memoryWriter[0xFF39] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[18] = $data >> 4;
            $parentObj->channel3PCM[19] = $data & 0xF;
            $parentObj->memory[0xFF39] = $data;
        };
        $this->memoryWriter[0xFF3A] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[20] = $data >> 4;
            $parentObj->channel3PCM[21] = $data & 0xF;
            $parentObj->memory[0xFF3A] = $data;
        };
        $this->memoryWriter[0xFF3B] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[22] = $data >> 4;
            $parentObj->channel3PCM[23] = $data & 0xF;
            $parentObj->memory[0xFF3B] = $data;
        };
        $this->memoryWriter[0xFF3C] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[24] = $data >> 4;
            $parentObj->channel3PCM[25] = $data & 0xF;
            $parentObj->memory[0xFF3C] = $data;
        };
        $this->memoryWriter[0xFF3D] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[26] = $data >> 4;
            $parentObj->channel3PCM[27] = $data & 0xF;
            $parentObj->memory[0xFF3D] = $data;
        };
        $this->memoryWriter[0xFF3E] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[28] = $data >> 4;
            $parentObj->channel3PCM[29] = $data & 0xF;
            $parentObj->memory[0xFF3E] = $data;
        };
        $this->memoryWriter[0xFF3F] = function ($parentObj, $address, $data) {
            $parentObj->channel3PCM[30] = $data >> 4;
            $parentObj->channel3PCM[31] = $data & 0xF;
            $parentObj->memory[0xFF3F] = $data;
        };
        $this->memoryWriter[0xFF44] = function ($parentObj, $address, $data) {
            //Read only
        };
        $this->memoryWriter[0xFF45] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF45] = $data;
            if ($parentObj->LCDisOn) {
                $parentObj->matchLYC();   //Get the compare of the first scan line.
            }
        };
        $this->memoryWriter[0xFF46] = function ($parentObj, $address, $data) {
            $parentObj->memory[0xFF46] = $data;
            if ($parentObj->cGBC || $data > 0x7F) {    //DMG cannot DMA from the ROM banks.
                $data <<= 8;
                $address = 0xFE00;
                while ($address < 0xFEA0) {
                    $parentObj->memory[$address++] = $parentObj->memoryReader[$data]($parentObj, $data++);
                }
            }
        };
        $this->memoryWriter[0xFF47] = function ($parentObj, $address, $data) {
            $parentObj->decodePalette(0, $data);
            if ($parentObj->memory[0xFF47] != $data) {
                $parentObj->memory[0xFF47] = $data;
                $parentObj->invalidateAll(0);
            }
        };
        $this->memoryWriter[0xFF48] = function ($parentObj, $address, $data) {
            $parentObj->decodePalette(4, $data);
            if ($parentObj->memory[0xFF48] != $data) {
                $parentObj->memory[0xFF48] = $data;
                $parentObj->invalidateAll(1);
            }
        };
        $this->memoryWriter[0xFF49] = function ($parentObj, $address, $data) {
            $parentObj->decodePalette(8, $data);
            if ($parentObj->memory[0xFF49] != $data) {
                $parentObj->memory[0xFF49] = $data;
                $parentObj->invalidateAll(2);
            }
        };
        if ($this->cGBC) {
            //GameBoy Color Specific I/O:
            $this->memoryWriter[0xFF40] = function ($parentObj, $address, $data) {
                $temp_var = ($data & 0x80) == 0x80;
                if ($temp_var != $parentObj->LCDisOn) {
                    //When the display mode changes...
                    $parentObj->LCDisOn = $temp_var;
                    $parentObj->memory[0xFF41] &= 0xF8;
                    $parentObj->STATTracker = $parentObj->modeSTAT = $parentObj->LCDTicks = $parentObj->actualScanLine = $parentObj->memory[0xFF44] = 0;
                    if ($parentObj->LCDisOn) {
                        $parentObj->matchLYC();   //Get the compare of the first scan line.
                        $parentObj->LCDCONTROL = $parentObj->LINECONTROL;
                    }
                    else {
                        $parentObj->LCDCONTROL = $parentObj->DISPLAYOFFCONTROL;
                        $parentObj->DisplayShowOff();
                    }
                    $parentObj->memory[0xFF0F] &= 0xFD;
                }
                $parentObj->gfxWindowY = ($data & 0x40) == 0x40;
                $parentObj->gfxWindowDisplay = ($data & 0x20) == 0x20;
                $parentObj->gfxBackgroundX = ($data & 0x10) == 0x10;
                $parentObj->gfxBackgroundY = ($data & 0x08) == 0x08;
                $parentObj->gfxSpriteDouble = ($data & 0x04) == 0x04;
                $parentObj->gfxSpriteShow = ($data & 0x02) == 0x02;
                $parentObj->spritePriorityEnabled = ($data & 0x01) == 0x01;
                $parentObj->memory[0xFF40] = $data;
            };
            $this->memoryWriter[0xFF41] = function ($parentObj, $address, $data) {
                $parentObj->LYCMatchTriggerSTAT = (($data & 0x40) == 0x40);
                $parentObj->mode2TriggerSTAT = (($data & 0x20) == 0x20);
                $parentObj->mode1TriggerSTAT = (($data & 0x10) == 0x10);
                $parentObj->mode0TriggerSTAT = (($data & 0x08) == 0x08);
                $parentObj->memory[0xFF41] = ($data & 0xF8);
            };
            $this->memoryWriter[0xFF4D] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF4D] = ($data & 0x7F) + ($parentObj->memory[0xFF4D] & 0x80);
            };
            $this->memoryWriter[0xFF4F] = function ($parentObj, $address, $data) {
                $parentObj->currVRAMBank = $data & 0x01;
                //Only writable by GBC.
            };
            $this->memoryWriter[0xFF51] = function ($parentObj, $address, $data) {
                if (!$parentObj->hdmaRunning) {
                    $parentObj->memory[0xFF51] = $data;
                }
            };
            $this->memoryWriter[0xFF52] = function ($parentObj, $address, $data) {
                if (!$parentObj->hdmaRunning) {
                    $parentObj->memory[0xFF52] = $data & 0xF0;
                }
            };
            $this->memoryWriter[0xFF53] = function ($parentObj, $address, $data) {
                if (!$parentObj->hdmaRunning) {
                    $parentObj->memory[0xFF53] = $data & 0x1F;
                }
            };
            $this->memoryWriter[0xFF54] = function ($parentObj, $address, $data) {
                if (!$parentObj->hdmaRunning) {
                    $parentObj->memory[0xFF54] = $data & 0xF0;
                }
            };
            $this->memoryWriter[0xFF55] = function ($parentObj, $address, $data) {
                if (!$parentObj->hdmaRunning) {
                    if (($data & 0x80) == 0) {
                        //DMA
                        $parentObj->CPUTicks += 1 + ((8 * (($data & 0x7F) + 1)) * $parentObj->multiplier);
                        $dmaSrc = ($parentObj->memory[0xFF51] << 8) + $parentObj->memory[0xFF52];
                        $dmaDst = 0x8000 + ($parentObj->memory[0xFF53] << 8) + $parentObj->memory[0xFF54];
                        $endAmount = ((($data & 0x7F) * 0x10) + 0x10);
                        for ($loopAmount = 0; $loopAmount < $endAmount; $loopAmount++) {
                            $parentObj->memoryWrite($dmaDst++, $parentObj->memoryRead($dmaSrc++));
                        }
                        $parentObj->memory[0xFF51] = (($dmaSrc & 0xFF00) >> 8);
                        $parentObj->memory[0xFF52] = ($dmaSrc & 0x00F0);
                        $parentObj->memory[0xFF53] = (($dmaDst & 0x1F00) >> 8);
                        $parentObj->memory[0xFF54] = ($dmaDst & 0x00F0);
                        $parentObj->memory[0xFF55] = 0xFF;    //Transfer completed.
                    }
                    else {
                        //H-Blank DMA
                        if ($data > 0x80) {
                            $parentObj->hdmaRunning = true;
                            $parentObj->memory[0xFF55] = $data & 0x7F;
                        }
                        else {
                            $parentObj->memory[0xFF55] = 0xFF;
                        }
                    }
                }
                else if (($data & 0x80) == 0) {
                    //Stop H-Blank DMA
                    $parentObj->hdmaRunning = false;
                    $parentObj->memory[0xFF55] |= 0x80;
                }
            };
            $this->memoryWriter[0xFF68] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF69] = 0xFF & $parentObj->gbcRawPalette[$data & 0x3F];
                $parentObj->memory[0xFF68] = $data;
            };
            $this->memoryWriter[0xFF69] = function ($parentObj, $address, $data) {
                $parentObj->setGBCPalette($parentObj->memory[0xFF68] & 0x3F, $data);
                if ($parentObj->usbtsb($parentObj->memory[0xFF68]) < 0) { // high bit = autoincrement
                    $next = (($parentObj->usbtsb($parentObj->memory[0xFF68]) + 1) & 0x3F);
                    $parentObj->memory[0xFF68] = ($next | 0x80);
                    $parentObj->memory[0xFF69] = 0xFF & $parentObj->gbcRawPalette[$next];
                }
                else {
                    $parentObj->memory[0xFF69] = $data;
                }
            };
            $this->memoryWriter[0xFF6A] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF6B] = 0xFF & $parentObj->gbcRawPalette[($data & 0x3F) | 0x40];
                $parentObj->memory[0xFF6A] = $data;
            };
            $this->memoryWriter[0xFF6B] = function ($parentObj, $address, $data) {
                $parentObj->setGBCPalette(($parentObj->memory[0xFF6A] & 0x3F) + 0x40, $data);
                if ($parentObj->usbtsb($parentObj->memory[0xFF6A]) < 0) { // high bit = autoincrement
                    $next = (($parentObj->memory[0xFF6A] + 1) & 0x3F);
                    $parentObj->memory[0xFF6A] = ($next | 0x80);
                    $parentObj->memory[0xFF6B] = 0xFF & $parentObj->gbcRawPalette[$next | 0x40];
                }
                else {
                    $parentObj->memory[0xFF6B] = $data;
                }
            };
            $this->memoryWriter[0xFF70] = function ($parentObj, $address, $data) {
                $addressCheck = ($parentObj->memory[0xFF51] << 8) | $parentObj->memory[0xFF52];  //Cannot change the RAM bank while WRAM is the source of a running HDMA.
                if (!$parentObj->hdmaRunning || $addressCheck < 0xD000 || $addressCheck >= 0xE000) {
                    $parentObj->gbcRamBank = max($data & 0x07, 1);    //Bank range is from 1-7
                    $parentObj->gbcRamBankPosition = (($parentObj->gbcRamBank - 1) * 0x1000) - 0xD000;
                    $parentObj->gbcRamBankPositionECHO = (($parentObj->gbcRamBank - 1) * 0x1000) - 0xF000;
                }
                $parentObj->memory[0xFF70] = ($data | 0x40);   //Bit 6 cannot be written to.
            };
        }
        else {
            //Fill in the GameBoy Color I/O registers as normal RAM for GameBoy compatibility:
            $this->memoryWriter[0xFF40] = function ($parentObj, $address, $data) {
                $temp_var = ($data & 0x80) == 0x80;
                if ($temp_var != $parentObj->LCDisOn) {
                    //When the display mode changes...
                    $parentObj->LCDisOn = $temp_var;
                    $parentObj->memory[0xFF41] &= 0xF8;
                    $parentObj->STATTracker = $parentObj->modeSTAT = $parentObj->LCDTicks = $parentObj->actualScanLine = $parentObj->memory[0xFF44] = 0;
                    if ($parentObj->LCDisOn) {
                        $parentObj->matchLYC();   //Get the compare of the first scan line.
                        $parentObj->LCDCONTROL = $parentObj->LINECONTROL;
                    }
                    else {
                        $parentObj->LCDCONTROL = $parentObj->DISPLAYOFFCONTROL;
                        $parentObj->DisplayShowOff();
                    }
                    $parentObj->memory[0xFF0F] &= 0xFD;
                }
                $parentObj->gfxWindowY = ($data & 0x40) == 0x40;
                $parentObj->gfxWindowDisplay = ($data & 0x20) == 0x20;
                $parentObj->gfxBackgroundX = ($data & 0x10) == 0x10;
                $parentObj->gfxBackgroundY = ($data & 0x08) == 0x08;
                $parentObj->gfxSpriteDouble = ($data & 0x04) == 0x04;
                $parentObj->gfxSpriteShow = ($data & 0x02) == 0x02;
                if (($data & 0x01) == 0) {
                    // this emulates the gbc-in-gb-mode, not the original gb-mode
                    $parentObj->bgEnabled = false;
                    $parentObj->gfxWindowDisplay = false;
                }
                else {
                    $parentObj->bgEnabled = true;
                }
                $parentObj->memory[0xFF40] = $data;
            };
            $this->memoryWriter[0xFF41] = function ($parentObj, $address, $data) {
                $parentObj->LYCMatchTriggerSTAT = (($data & 0x40) == 0x40);
                $parentObj->mode2TriggerSTAT = (($data & 0x20) == 0x20);
                $parentObj->mode1TriggerSTAT = (($data & 0x10) == 0x10);
                $parentObj->mode0TriggerSTAT = (($data & 0x08) == 0x08);
                $parentObj->memory[0xFF41] = ($data & 0xF8);
                if ($parentObj->LCDisOn && $parentObj->modeSTAT < 2) {
                    $parentObj->memory[0xFF0F] |= 0x2;
                }
            };
            $this->memoryWriter[0xFF4D] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF4D] = $data;
            };
            $this->memoryWriter[0xFF4F] = function ($parentObj, $address, $data) {
                //Not writable in DMG mode.
            };
            $this->memoryWriter[0xFF55] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF55] = $data;
            };
            $this->memoryWriter[0xFF68] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF68] = $data;
            };
            $this->memoryWriter[0xFF69] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF69] = $data;
            };
            $this->memoryWriter[0xFF6A] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF6A] = $data;
            };
            $this->memoryWriter[0xFF6B] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF6B] = $data;
            };
            $this->memoryWriter[0xFF70] = function ($parentObj, $address, $data) {
                $parentObj->memory[0xFF70] = $data;
            };
        }
        //Boot I/O Registers:
        if ($this->inBootstrap) {
            $this->memoryWriter[0xFF50] = function ($parentObj, $address, $data) {
                echo "Boot ROM reads blocked: Bootstrap process has ended." . PHP_EOL;
                $parentObj->inBootstrap = false;
                $parentObj->disableBootROM();         //Fill in the boot ROM ranges with ROM  bank 0 ROM ranges
                $parentObj->memory[0xFF50] = $data;    //Bits are sustained in memory?
            };
            $this->memoryWriter[0xFF6C] = function ($parentObj, $address, $data) {
                if ($parentObj->inBootstrap) {
                    $parentObj->cGBC = ($data == 0x80);
                    echo "Booted to GBC Mode: " + $parentObj->cGBC . PHP_EOL;
                }
                $parentObj->memory[0xFF6C] = $data;
            };
        }
        else {
            //Lockout the ROMs from accessing the BOOT ROM control register:
            $this->memoryWriter[0xFF6C] = $this->memoryWriter[0xFF50] = function ($parentObj, $address, $data) { };
        }
    }
    //Helper Functions
    public function usbtsb($ubyte) {
        //Unsigned byte to signed byte:
        return ($ubyte > 0x7F) ? (($ubyte & 0x7F) - 0x80) : $ubyte;
    }

    public function unsbtub($ubyte) {
        //Keep an unsigned byte unsigned:
        if ($ubyte < 0) {
            $ubyte += 0x100;
        }
        return $ubyte;   //If this function is called, no wrapping requested.
    }

    public function nswtuw($uword) {
        //Keep an unsigned word unsigned:
        if ($uword < 0) {
            $uword += 0x10000;
        }
        return $uword & 0xFFFF;  //Wrap also...
    }

    public function unswtuw($uword) {
        //Keep an unsigned word unsigned:
        if ($uword < 0) {
            $uword += 0x10000;
        }
        return $uword;   //If this function is called, no wrapping requested.
    }

    public function toTypedArray($baseArray, $bit32, $unsigned) {
        try {
            $typedArrayTemp = ($bit32) ? (($unsigned) ? new Uint32Array(count($baseArray)) : new Int32Array(count($baseArray))) : new Uint8Array(count($baseArray));
            for ($index = 0; $index < count($baseArray); $index++) {
                $typedArrayTemp[$index] = $baseArray[$index];
            }
            return $typedArrayTemp;
        }
        catch (\Exception $error) {
            echo "Could not convert an array to a typed array" . PHP_EOL;
            return $baseArray;
        }
    }

    public function fromTypedArray($baseArray) {
        try {
            $arrayTemp = array_fill(0, count($baseArray), 0);
            for ($index = 0; $index < count($baseArray); $index++) {
                $arrayTemp[$index] = $baseArray[$index];
            }
            return $arrayTemp;
        }
        catch (\Exception $error) {
            return $baseArray;
        }
    }

    public function getTypedArray($length, $defaultValue, $numberType) {
        try {
            if (Settings::$settings[22]) {
                throw(new Error(""));
            }

            /*
            switch ($numberType) {
                case "uint8":
                    $arrayHandle = new Uint8Array($length);
                    break;
                case "int8":
                    $arrayHandle = new Int8Array($length);
                    break;
                case "uint16":
                    $arrayHandle = new Uint16Array($length);
                    break;
                case "int16":
                    $arrayHandle = new Int16Array($length);
                    break;
                case "uint32":
                    $arrayHandle = new Uint32Array($length);
                    break;
                case "int32":
                    $arrayHandle = new Int32Array($length);
                    break;
                case "float32":
                    $arrayHandle = new Float32Array($length);
            }
            */

            $arrayHandle = array_fill(0, $length, 0);

            if ($defaultValue > 0) {
                $index = 0;
                while ($index < $length) {
                    $arrayHandle[$index++] = $defaultValue;
                }
            }
        }
        catch (\Exception $error) {
            $arrayHandle = array_fill(0, $length, 0);
            $index = 0;
            while ($index < $length) {
                $arrayHandle[$index++] = $defaultValue;
            }
        }
        return $arrayHandle;
    }

    public function ArrayPad($length, $defaultValue) {
        $arrayHandle = array_fill(0, $length, 0);
        $index = 0;
        while ($index < $length) {
            $arrayHandle[$index++] = $defaultValue;
        }
        return $arrayHandle;
    }
}