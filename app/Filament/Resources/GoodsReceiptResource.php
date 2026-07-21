<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Filament\Resources\GoodsReceiptResource\RelationManagers;
use App\Models\GoodsReceipt;
use App\Models\PaymentMilestone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptResource extends Resource
{
    use \App\Filament\Resources\Concerns\HasYearFilter;

    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Goods & Service Receipt (GR/MR)';

    protected static ?string $modelLabel = 'ใบตรวจรับ';

    protected static ?string $pluralModelLabel = 'ใบตรวจรับงาน/วัสดุ';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 8;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'warehouse_staff', 'inspection_committee', 'auditor']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ข้อมูลใบสั่งซื้อ')
                    ->schema([
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('เลือกใบสั่งซื้อ (PO)')
                            ->relationship('purchaseOrder', 'po_number', function ($query) {
                                // ตรวจรับได้กับ PO ที่ผ่านการอนุมัติแล้วทุกสถานะ (รวมที่กำลัง/รับของครบ และปิดงานแล้ว)
                                // เดิมกรองแค่ 'approved' ทำให้เลือก PO ไม่ได้ เพราะ PO ที่ส่งของแล้วจะเลื่อนสถานะไปจาก approved
                                return $query->whereIn('status', [
                                    'approved',
                                    'sent_to_supplier',
                                    'acknowledged',
                                    'partially_received',
                                    'fully_received',
                                    'closed',
                                ]);
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // Reset milestone selection when PO changes
                                $set('payment_milestone_id', null);
                                $set('delivery_milestone', null);
                                $set('milestone_percentage', null);
                                $set('milestone_amount', null);

                                if ($state) {
                                    $connection = session('company_connection', 'mysql');

                                    $po = \App\Models\PurchaseOrder::on($connection)
                                        ->with(['vendor', 'supplier'])
                                        ->find($state);

                                    if ($po) {
                                        $vendor = $po->vendor ?: $po->supplier;
                                        if ($vendor) {
                                            $set('vendor_id', $vendor->id);
                                            $set('vendor_name_display', $vendor->company_name);
                                        }
                                    }
                                }
                            })
                            ->required(),

                        Forms\Components\Select::make('payment_milestone_id')
                            ->label('งวดการส่งมอบ')
                            ->options(function (Forms\Get $get, ?GoodsReceipt $record) {
                                $poId = $get('purchase_order_id');
                                if (! $poId) {
                                    return [];
                                }

                                $connection = session('company_connection', 'mysql');

                                return PaymentMilestone::on($connection)
                                    ->where('purchase_order_id', $poId)
                                    ->where(function ($query) use ($record) {
                                        $query->whereDoesntHave('goodsReceipt');
                                        // Include the milestone already linked to the current GR (edit mode)
                                        if ($record?->payment_milestone_id) {
                                            $query->orWhere('id', $record->payment_milestone_id);
                                        }
                                    })
                                    ->orderBy('milestone_number')
                                    ->get()
                                    ->mapWithKeys(fn ($m) => [
                                        $m->id => "งวดที่ {$m->milestone_number} - {$m->milestone_title} ({$m->percentage}%)",
                                    ]);
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $connection = session('company_connection', 'mysql');
                                    $milestone = PaymentMilestone::on($connection)->find($state);
                                    if ($milestone) {
                                        $set('delivery_milestone', $milestone->milestone_number);
                                        $set('milestone_percentage', $milestone->percentage);
                                    }
                                } else {
                                    $set('delivery_milestone', null);
                                    $set('milestone_percentage', null);
                                }
                            })
                            ->placeholder('เลือก PO ก่อน แล้วเลือกงวด (ถ้ามี)')
                            // ข้อ 8: ไม่บังคับเลือกงวด — กรณี PO ไม่มีงวดการจ่ายในระบบ
                            // ผู้ใช้สามารถระบุ "งวดที่" และ "เปอร์เซ็นต์" ด้านล่างได้เอง
                            // helperText ปรับตามสถานะจริง เพื่อไม่ให้ผู้ใช้งงว่าทำไมเลือกงวดไม่ได้
                            ->helperText(function (Forms\Get $get): string {
                                $poId = $get('purchase_order_id');
                                if (! $poId) {
                                    return 'เลือกใบสั่งซื้อ (PO) ก่อน จึงจะแสดงงวดการส่งมอบ';
                                }

                                $connection = session('company_connection', 'mysql');
                                $totalMilestones = PaymentMilestone::on($connection)
                                    ->where('purchase_order_id', $poId)->count();
                                $freeMilestones = PaymentMilestone::on($connection)
                                    ->where('purchase_order_id', $poId)
                                    ->whereDoesntHave('goodsReceipt')->count();

                                if ($totalMilestones === 0) {
                                    return 'PO นี้ไม่มีงวดการจ่ายในระบบ — ไม่ต้องเลือกงวด ให้ระบุ "งวดที่" และ "เปอร์เซ็นต์" ด้านล่างเองได้';
                                }

                                if ($freeMilestones === 0) {
                                    return 'ทุกงวดของ PO นี้ถูกตรวจรับครบแล้ว — หากต้องการตรวจรับเพิ่ม ให้ระบุ "งวดที่" และ "เปอร์เซ็นต์" ด้านล่างเอง';
                                }

                                return 'แสดงเฉพาะงวดที่ยังไม่ได้ตรวจรับ — หาก PO นี้ยังไม่มีงวดในระบบ ให้ระบุงวดที่/เปอร์เซ็นต์ด้านล่างเอง';
                            }),

                        Forms\Components\Hidden::make('vendor_id')
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('vendor_name_display')
                            ->label('ผู้ขาย')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('เลือก PO เพื่อดึงข้อมูลผู้ขาย')
                            ->helperText('ดึงข้อมูลจาก PO อัตโนมัติ'),
                        Forms\Components\Select::make('inspection_committee_id')
                            ->label('คณะกรรมการตรวจสอบ')
                            ->relationship('inspectionCommittee', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('ข้อมูลการตรวจรับ')
                    ->schema([
                        Forms\Components\TextInput::make('gr_number')
                            ->label('เลขที่ GR')
                            ->disabled()
                            ->placeholder('จะถูกสร้างอัตโนมัติ')
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('receipt_date')
                            ->label('วันที่รับ')
                            ->required()
                            ->default(now()),
                        // ข้อ 8: แก้ไขได้เอง เมื่อ PO ไม่มีงวดในระบบ
                        // (auto-fill จาก dropdown ด้านบนหากเลือกงวด)
                        Forms\Components\TextInput::make('delivery_milestone')
                            ->label('งวดที่')
                            ->numeric()
                            ->minValue(1)
                            ->dehydrated(true)
                            ->placeholder('ระบุงวดที่ หรือเลือกจากงวดการส่งมอบด้านบน')
                            ->helperText('ระบุเองได้ หรือดึงจากงวดการส่งมอบอัตโนมัติ'),
                        Forms\Components\TextInput::make('milestone_percentage')
                            ->label('เปอร์เซ็นต์')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->dehydrated(true)
                            ->suffix('%')
                            ->placeholder('เช่น 25')
                            ->helperText('ระบุเองได้ — ค่าที่กรอกเองจะถูกใช้แสดงผล/คำนวณแทน % ของงวดในระบบ'),
                        Forms\Components\TextInput::make('milestone_amount')
                            ->label('จำนวนเงินตรวจรับงวดนี้')
                            ->numeric()
                            ->minValue(0)
                            ->dehydrated(true)
                            ->suffix('บาท')
                            ->placeholder('เช่น 9000.00')
                            ->helperText('ระบุเองได้ — ถ้าเว้นว่างระบบจะคำนวณจากมูลค่าสัญญา × เปอร์เซ็นต์'),
                        Forms\Components\Select::make('inspection_status')
                            ->label('สถานะตรวจสอบ')
                            ->required()
                            ->options([
                                'pending' => 'รอตรวจสอบ',
                                'passed' => 'ผ่านการตรวจสอบ',
                                'failed' => 'ไม่ผ่านการตรวจสอบ',
                                'partial' => 'ผ่านบางส่วน',
                            ])
                            ->default('pending'),
                        Forms\Components\Select::make('status')
                            ->label('สถานะ')
                            ->required()
                            ->options([
                                'draft' => 'แบบร่าง',
                                'completed' => 'เสร็จสมบูรณ์',
                                'returned' => 'ส่งคืน',
                                'partially_returned' => 'ส่งคืนบางส่วน',
                                'cancelled' => 'ยกเลิก',
                            ])
                            ->default('draft'),
                    ])->columns(3),

                Forms\Components\Section::make('หมายเหตุ')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('หมายเหตุ')
                            ->rows(3),
                        Forms\Components\Textarea::make('inspection_notes')
                            ->label('หมายเหตุการตรวจสอบ')
                            ->rows(3),
                    ])->columns(2),

                // NOTE: File attachments are managed exclusively via the
                // AttachmentsRelationManager (the "เอกสารแนบ" table below
                // the form). The previous inline FileUpload here did not
                // persist GoodsReceiptAttachment records on edit, causing
                // files to vanish after refresh.
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('ข้อมูลใบตรวจรับ')
                    ->schema([
                        Infolists\Components\TextEntry::make('gr_number')
                            ->label('เลขที่ GR'),
                        Infolists\Components\TextEntry::make('purchaseOrder.po_number')
                            ->label('เลขที่ PO'),
                        Infolists\Components\TextEntry::make('vendor.company_name')
                            ->label('ผู้ขาย'),
                        // ข้อ 2: แสดงวันที่เป็น วัน/เดือน/ปี
                        Infolists\Components\TextEntry::make('receipt_date')
                            ->label('วันที่รับ')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('delivery_milestone')
                            ->label('งวดที่')
                            ->formatStateUsing(function ($state, $record) {
                                // ค่าที่กรอกเองใน GR มาก่อนงวดในระบบ
                                $num = $state ?: $record->paymentMilestone?->milestone_number;

                                return $num ? "งวดที่ {$num}" : '-';
                            }),
                        Infolists\Components\TextEntry::make('inspectionCommittee.name')
                            ->label('คณะกรรมการตรวจสอบ')
                            ->placeholder('-'),
                    ])->columns(3),

                // ข้อ 1: แสดงมูลค่าสัญญา และจำนวนเงินที่ตรวจรับในงวดนั้นๆ
                Infolists\Components\Section::make('มูลค่าและจำนวนเงินตรวจรับ')
                    ->schema([
                        Infolists\Components\TextEntry::make('contract_value')
                            ->label('มูลค่าสัญญา')
                            ->state(fn ($record) => $record->purchaseOrder?->total_amount)
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).' บาท' : '-'),
                        Infolists\Components\TextEntry::make('milestone_amount')
                            ->label('จำนวนเงินตรวจรับงวดนี้')
                            ->state(fn ($record) => $record->effective_amount)
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).' บาท' : '-'),
                        Infolists\Components\TextEntry::make('milestone_percentage')
                            ->label('เปอร์เซ็นต์')
                            ->state(fn ($record) => $record->effective_percentage)
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).' %' : '-'),
                    ])->columns(3),

                Infolists\Components\Section::make('สถานะ')
                    ->schema([
                        Infolists\Components\TextEntry::make('inspection_status')
                            ->label('สถานะตรวจสอบ')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'pending' => 'รอตรวจสอบ',
                                'passed' => 'ผ่านการตรวจสอบ',
                                'failed' => 'ไม่ผ่านการตรวจสอบ',
                                'partial' => 'ผ่านบางส่วน',
                                default => $state,
                            })
                            ->color(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'passed' => 'success',
                                'failed' => 'danger',
                                'partial' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->label('สถานะ')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'draft' => 'แบบร่าง',
                                'completed' => 'เสร็จสมบูรณ์',
                                'returned' => 'ส่งคืน',
                                'partially_returned' => 'ส่งคืนบางส่วน',
                                'cancelled' => 'ยกเลิก',
                                default => $state,
                            })
                            ->color(fn ($state) => match ($state) {
                                'draft' => 'gray',
                                'completed' => 'success',
                                'returned' => 'warning',
                                'partially_returned' => 'info',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(2),

                Infolists\Components\Section::make('หมายเหตุ')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('หมายเหตุ')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('inspection_notes')
                            ->label('หมายเหตุการตรวจสอบ')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gr_number')
                    ->label('เลขที่ GR')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('เลขที่ PO')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.company_name')
                    ->label('ผู้ขาย')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('inspectionCommittee.name')
                    ->label('คณะกรรมการตรวจสอบ')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('วันที่รับ')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMilestone.milestone_title')
                    ->label('งวด')
                    ->formatStateUsing(function ($state, $record) {
                        // ค่าที่กรอกเองใน GR มาก่อนงวดในระบบ
                        $num = $record->delivery_milestone ?: $record->paymentMilestone?->milestone_number;
                        if ($record->paymentMilestone) {
                            return "งวดที่ {$num} - {$state}";
                        }

                        return $num ? "งวดที่ {$num}" : '-';
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('milestone_percentage')
                    ->label('%')
                    ->state(fn ($record) => $record->effective_percentage)
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->alignCenter(),
                Tables\Columns\BadgeColumn::make('inspection_status')
                    ->label('สถานะตรวจสอบ')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'รอตรวจสอบ',
                        'passed' => 'ผ่านการตรวจสอบ',
                        'failed' => 'ไม่ผ่านการตรวจสอบ',
                        'partial' => 'ผ่านบางส่วน',
                        default => $state
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'passed' => 'success',
                        'failed' => 'danger',
                        'partial' => 'info',
                        default => 'secondary'
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('สถานะ')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'แบบร่าง',
                        'completed' => 'เสร็จสมบูรณ์',
                        'returned' => 'ส่งคืน',
                        'partially_returned' => 'ส่งคืนบางส่วน',
                        'cancelled' => 'ยกเลิก',
                        default => $state
                    })
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                        'returned' => 'warning',
                        'partially_returned' => 'info',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    }),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('ผู้สร้าง')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('email_status')
                    ->label('สถานะ Email')
                    ->getStateUsing(function ($record) {
                        if ($record->reminder_sent_at) {
                            return 'sent-manual';
                        } elseif ($record->committee_notified_at) {
                            return 'sent-auto';
                        } else {
                            return 'not-sent';
                        }
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'sent-manual' => 'heroicon-o-bell',
                        'sent-auto' => 'heroicon-o-check-circle',
                        'not-sent' => 'heroicon-o-x-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sent-manual' => 'info',
                        'sent-auto' => 'success',
                        'not-sent' => 'danger',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'sent-manual' => 'ส่งเตือนด้วยตนเอง',
                        'sent-auto' => 'ส่งอัตโนมัติแล้ว',
                        'not-sent' => 'ยังไม่ได้ส่ง',
                    })
                    ->sortable(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่สร้าง')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('สถานะ')
                    ->options([
                        'draft' => 'แบบร่าง',
                        'completed' => 'เสร็จสมบูรณ์',
                        'returned' => 'ส่งคืน',
                        'partially_returned' => 'ส่งคืนบางส่วน',
                        'cancelled' => 'ยกเลิก',
                    ]),
                Tables\Filters\SelectFilter::make('inspection_status')
                    ->label('สถานะตรวจสอบ')
                    ->options([
                        'pending' => 'รอตรวจสอบ',
                        'passed' => 'ผ่านการตรวจสอบ',
                        'failed' => 'ไม่ผ่านการตรวจสอบ',
                        'partial' => 'ผ่านบางส่วน',
                    ]),
                Tables\Filters\Filter::make('receipt_date')
                    ->label('ช่วงวันที่รับ')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('จากวันที่'),
                        Forms\Components\DatePicker::make('until')
                            ->label('ถึงวันที่'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '<=', $date));
                    }),

                // กรองตามปีของเอกสาร (วันที่รับของ) ไม่ใช่วันที่สร้างในระบบ
                static::yearFilter('receipt_date'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('ดู'),
                Tables\Actions\EditAction::make()
                    ->label('แก้ไข'),
                Tables\Actions\Action::make('sendNotification')
                    ->label(function ($record) {
                        if ($record->reminder_sent_at) {
                            return 'ส่งเตือนอีกครั้ง';
                        } elseif ($record->committee_notified_at) {
                            return 'ส่งเตือนอีกครั้ง';
                        } else {
                            return 'แจ้งเตือนคณะกรรมการ';
                        }
                    })
                    ->icon('heroicon-o-bell')
                    ->color(function ($record) {
                        if ($record->reminder_sent_at || $record->committee_notified_at) {
                            return 'warning';  // ส่งอีกครั้ง
                        } else {
                            return 'info';     // ส่งครั้งแรก
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('ส่งการแจ้งเตือนใบตรวจรับ')
                    ->modalDescription('คุณต้องการส่งการแจ้งเตือนใบตรวจรับนี้ให้คณะกรรมการตรวจสอบหรือไม่?')
                    ->modalSubmitActionLabel('ส่งการแจ้งเตือน')
                    ->action(function ($record) {
                        $creator = \App\Models\User::find(auth()->id());

                        if (! $record->inspection_committee_id) {
                            \Filament\Notifications\Notification::make()
                                ->title('ไม่พบคณะกรรมการ')
                                ->body('กรุณาเลือกคณะกรรมการตรวจสอบก่อนส่งการแจ้งเตือน')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            // Send email immediately (sync)
                            $goodsReceipt = \App\Models\GoodsReceipt::with(['purchaseOrder', 'vendor', 'inspectionCommittee'])->find($record->id);

                            if ($goodsReceipt->inspectionCommittee && $goodsReceipt->inspectionCommittee->email) {
                                // Send to inspection committee
                                \Illuminate\Support\Facades\Mail::to($goodsReceipt->inspectionCommittee->email)
                                    ->send(new \App\Mail\GoodsReceiptNotificationMail($goodsReceipt, $creator));

                                // Send copy to creator if different email
                                if ($creator->email !== $goodsReceipt->inspectionCommittee->email) {
                                    \Illuminate\Support\Facades\Mail::to($creator->email)
                                        ->send(new \App\Mail\GoodsReceiptNotificationMail($goodsReceipt, $creator, true));
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('ส่งการแจ้งเตือนแล้ว')
                                ->body('ส่งการแจ้งเตือนใบตรวจรับให้คณะกรรมการเรียบร้อยแล้ว')
                                ->success()
                                ->send();

                            // Update reminder timestamp
                            $record->update(['reminder_sent_at' => now()]);

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('เกิดข้อผิดพลาด')
                                ->body('ไม่สามารถส่งการแจ้งเตือนได้: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->inspection_committee_id !== null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('ลบ'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'view' => Pages\ViewGoodsReceipt::route('/{record}'),
            'edit' => Pages\EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
